<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LicenseVerificationService
{
    public const MASTER_DOMAIN = 'bmitltd.com';

    public const VERIFY_URL = 'https://www.bmitltd.com/api/verify-license';

    public const MASTER_LICENSE_PAGE = 'https://www.bmitltd.com/license';

    public const SIGNAL_URL = 'https://www.bmitltd.com/api/cache-signal';

    public const FETCH_URL = 'https://www.bmitltd.com/api/fetch-license';

    public const HMAC_SALT = 'your_secret_salt_key';

    /** ভ্যালিড হলে আর API চেক হবে না (ঘণ্টা) */
    public const VALIDATED_CACHE_HOURS = 100;

    public function currentDomain(?Request $request = null): string
    {
        $request = $request ?? request();
        $domain  = strtolower(str_replace(['www.', 'http://', 'https://'], '', $request->getHost()));

        if (in_array($domain, ['127.0.0.1', '::1'], true)) {
            $appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST);
            if ($appHost) {
                return strtolower(str_replace('www.', '', $appHost));
            }

            return 'localhost';
        }

        return $domain;
    }

    public function isMasterDomain(?Request $request = null): bool
    {
        return $this->currentDomain($request) === self::MASTER_DOMAIN;
    }

    /** ডোমেইন + লাইসেন্স কী — নতুন হোস্টিং এ পুরনো ১০০ঘ ক্যাশ কাজ করবে না */
    public function cacheKey(?Request $request = null): string
    {
        $domain = $this->currentDomain($request);
        $licenseKey = $this->getLicenseKey() ?? '';

        return '_session_validator_v3_' . substr(md5($domain . '|' . $licenseKey), 0, 12);
    }

    public function signalCacheKey(?Request $request = null): string
    {
        return '_last_signal_' . md5($this->currentDomain($request));
    }

    public function signalPollCacheKey(?Request $request = null): string
    {
        return '_signal_poll_' . md5($this->currentDomain($request));
    }

    public function getLicenseKey(): ?string
    {
        $fromFile = $this->readEnvValue('LICENSE_KEY');

        return $fromFile !== null && $fromFile !== '' ? $fromFile : env('LICENSE_KEY');
    }

    public function getLicenseSignature(): ?string
    {
        $fromFile = $this->readEnvValue('LICENSE_SIGNATURE');

        return $fromFile !== null && $fromFile !== '' ? $fromFile : env('LICENSE_SIGNATURE');
    }

    public function hasLicenseCredentials(): bool
    {
        return !empty($this->getLicenseKey()) && !empty($this->getLicenseSignature());
    }

    public function isValidatedCached(?Request $request = null): bool
    {
        $state = Cache::get($this->cacheKey($request));

        return in_array($state, ['verified', 'grace'], true);
    }

    public function cacheMetaKey(?Request $request = null): string
    {
        return $this->cacheKey($request) . '_meta';
    }

    /** API valid হলে verified ক্যাশ + মেটা (এডমিন পেজে দেখানোর জন্য) */
    public function rememberValidatedLicense(?Request $request = null, string $state = 'verified', ?int $hours = null): void
    {
        $hours = $hours ?? ($state === 'grace' ? 12 : self::VALIDATED_CACHE_HOURS);
        $expires = now()->addHours($hours);
        $key = $this->cacheKey($request);

        Cache::put($key, $state, $expires);
        Cache::put($this->cacheMetaKey($request), [
            'state'      => $state,
            'cached_at'  => now()->format('Y-m-d H:i:s'),
            'expires_at' => $expires->format('Y-m-d H:i:s'),
            'hours'      => $hours,
            'cache_key'  => $key,
        ], $expires);
    }

    public function forgetValidatedLicenseCache(?Request $request = null): void
    {
        Cache::forget($this->cacheKey($request));
        Cache::forget($this->cacheMetaKey($request));
    }

    /**
     * এডমিন লাইসেন্স পেজে ১০০ ঘণ্টা ক্যাশ স্ট্যাটাস দেখানোর জন্য।
     */
    public function getCacheStatus(?Request $request = null): array
    {
        $request = $request ?? request();
        $state = Cache::get($this->cacheKey($request));
        $meta = Cache::get($this->cacheMetaKey($request)) ?? [];
        $active = in_array($state, ['verified', 'grace'], true);

        $expiresAt = !empty($meta['expires_at'])
            ? \Carbon\Carbon::parse($meta['expires_at'])
            : null;

        return [
            'active'            => $active,
            'state'             => $state ?? 'none',
            'cache_key'         => $this->cacheKey($request),
            'hours_configured'  => self::VALIDATED_CACHE_HOURS,
            'cached_at'         => $meta['cached_at'] ?? null,
            'expires_at'        => $meta['expires_at'] ?? null,
            'remaining_label'   => ($active && $expiresAt && $expiresAt->isFuture())
                ? $expiresAt->diffForHumans(['parts' => 3, 'short' => false])
                : null,
            'api_skipped'       => $active,
            'admin_locked'      => $this->isLockedByServer($request),
            'admin_lock_message'=> $this->getServerInvalidMessage($request),
            'has_credentials'   => $this->hasLicenseCredentials(),
            'driver'            => config('cache.default'),
        ];
    }

    /**
     * Layer 1 — ক্যাশ নেইলে একবার verify, থাকলে ১০০ঘ স্কিপ (রেফারেন্স AppServiceProvider)।
     */
    public function bootstrapValidatedCache(?Request $request = null): void
    {
        $request = $request ?? request();

        if ($this->isMasterDomain($request) || !$this->hasLicenseCredentials()) {
            return;
        }

        if ($this->isValidatedCached($request)) {
            $this->pollServerSignalIfNeeded($request);

            return;
        }

        $this->verifyWithServer($request, false, false);
    }

    public function readEnvValue(string $key): ?string
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return null;
        }

        $content = file_get_contents($envPath);
        if (preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $content, $matches)) {
            $value = trim($matches[1]);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $value = trim($value, "\"'");
            }

            return $value;
        }

        return null;
    }

    public function writeEnvValues(array $values): bool
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath) || !is_writable($envPath)) {
            return false;
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $line = $this->formatEnvLine($key, (string) $value);
            if (preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
                $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $content);
            } else {
                $content = rtrim($content) . "\n" . $line . "\n";
            }
        }

        if (file_put_contents($envPath, $content) === false) {
            return false;
        }

        $this->reloadConfigAfterEnvWrite();

        return true;
    }

    private function formatEnvLine(string $key, string $value): string
    {
        if ($value === '') {
            return $key . '=';
        }

        if (preg_match('/[\s#="\']/', $value)) {
            return $key . '="' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }

        return $key . '=' . $value;
    }

    private function reloadConfigAfterEnvWrite(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        try {
            if (class_exists(\Illuminate\Support\Facades\Artisan::class)) {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Backward-compatible alias used by legacy flows (/super/7575/clear).
     */
    public function clearConfigCache(): void
    {
        $this->reloadConfigAfterEnvWrite();
        $this->clearAppDataCaches();
    }

    public function ensureLicenseCredentials(?Request $request = null): bool
    {
        if ($this->hasLicenseCredentials()) {
            return true;
        }

        return $this->autoSyncFromMaster($request);
    }

    public function autoSyncFromMaster(?Request $request = null): bool
    {
        $request = $request ?? request();
        $domain = $this->currentDomain($request);
        $syncLockKey = '_lic_sync_lock_' . md5($domain);
        Cache::forget($syncLockKey);

        $hour = gmdate('YmdH');
        $prevHour = gmdate('YmdH', strtotime('-1 hour'));
        $tokens = [
            hash_hmac('sha256', $domain . '|' . $hour, self::HMAC_SALT),
            hash_hmac('sha256', $domain . '|' . $prevHour, self::HMAC_SALT),
        ];

        foreach ($tokens as $token) {
            $data = $this->fetchLicenseData($domain, $token);
            if ($data === null) {
                continue;
            }

            $envValues = [
                'LICENSE_KEY' => $data['license_key'],
                'LICENSE_SIGNATURE' => $data['signature_key'] ?? '',
            ];

            $expiry = $data['expiry_date'] ?? '';
            if ($expiry !== '' && strtolower($expiry) !== 'lifetime') {
                $envValues['LICENSE_EXPIRY'] = $expiry;
            }

            if (!$this->writeEnvValues($envValues)) {
                continue;
            }

            Cache::forget($syncLockKey);
            $this->clearConfigCache();

            return true;
        }

        Cache::put($syncLockKey, 1, now()->addMinutes(2));

        return false;
    }

    private function fetchLicenseData(string $domain, string $token): ?array
    {
        $url = self::FETCH_URL . '?domain=' . urlencode($domain) . '&token=' . urlencode($token);

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(8)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'ok' && !empty($data['license_key'])) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // cURL fallback
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            curl_close($ch);

            if ($body) {
                $data = json_decode($body, true);
                if (is_array($data) && ($data['status'] ?? '') === 'ok' && !empty($data['license_key'])) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }

    public function adminLockCacheKey(?Request $request = null): string
    {
        return 'admin_panel_server_invalid_' . md5($this->currentDomain($request));
    }

    public function serverInvalidMessageKey(?Request $request = null): string
    {
        return 'admin_panel_server_invalid_msg_' . md5($this->currentDomain($request));
    }

    public function isLockedByServer(?Request $request = null): bool
    {
        return (bool) Cache::get($this->adminLockCacheKey($request), false);
    }

    public function getServerInvalidMessage(?Request $request = null): ?string
    {
        return Cache::get($this->serverInvalidMessageKey($request));
    }

    public function lockFromServer(string $reason = '', ?Request $request = null): void
    {
        Cache::put($this->adminLockCacheKey($request), true, now()->addDays(365));

        if ($reason !== '') {
            Cache::put($this->serverInvalidMessageKey($request), $reason, now()->addDays(7));
        }
    }

    public function unlockFromServer(?Request $request = null): void
    {
        Cache::forget($this->adminLockCacheKey($request));
        Cache::forget($this->serverInvalidMessageKey($request));
    }

    public function masterLicenseRedirectUrl(?Request $request = null, ?string $domain = null): string
    {
        $host = $domain ?? $this->currentDomain($request);

        return self::MASTER_LICENSE_PAGE . '?domain=' . urlencode($host);
    }

    public function clearAllLicenseCaches(?Request $request = null): void
    {
        $request = $request ?? request();
        $domain = $this->currentDomain($request);
        $hash = md5($domain);

        $this->unlockFromServer($request);
        $this->forgetValidatedLicenseCache($request);
        Cache::forget('_lic_sync_lock_' . $hash);
        Cache::forget($this->signalPollCacheKey($request));
        Cache::forget($this->signalCacheKey($request));
        Cache::forget('_last_signal_sp_' . $hash);
        Cache::forget('_session_validator_v3');
    }

    /**
     * ক্যাশ ক্লিয়ার / license-check — বাধ্য API চেক, invalid হলে Creative Design।
     */
    public function enforceFreshLicenseCheck(Request $request)
    {
        $this->autoSyncFromMaster($request);

        if (!$this->hasLicenseCredentials()) {
            return $this->redirectToCreativeDesign($request);
        }

        $outcome = $this->verifyWithServer($request, true);

        if ($outcome === 'valid') {
            return null;
        }

        if ($outcome === 'grace' || $outcome === 'unchanged') {
            return null;
        }

        return $this->redirectToCreativeDesign($request);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse|null
     */
    public function redirectIfMissingLicense(Request $request)
    {
        if ($this->isMasterDomain($request) || $this->shouldBypassLicenseCheck($request)) {
            return null;
        }

        $this->autoSyncFromMaster($request);

        if (!$this->hasLicenseCredentials()) {
            return $this->redirectToCreativeDesign($request);
        }

        return null;
    }

    /**
     * এডমিন প্যানেল — API invalid = লক পেজ; কী নেই = CD; verified ক্যাশ ১০০ঘ।
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse|null
     */
    public function enforceAdminLicense(Request $request, bool $creativeDesignOnFailure = false)
    {
        if ($this->isMasterDomain($request) || !$this->isAdminPanelRequest($request)) {
            return null;
        }

        if ($this->shouldBypassLicenseCheck($request)) {
            return null;
        }

        if ($creativeDesignOnFailure) {
            return $this->enforceFreshLicenseCheck($request);
        }

        $this->autoSyncFromMaster($request);

        if (!$this->hasLicenseCredentials()) {
            return $this->redirectToCreativeDesign($request);
        }

        if ($this->isLockedByServer($request)) {
            return $this->redirectToCreativeDesign($request);
        }

        // ১০০ ঘণ্টা verified ক্যাশ — API আবার যাবে না (রেফারেন্স মতো)
        if ($this->isValidatedCached($request)) {
            $this->pollServerSignalIfNeeded($request);

            if ($this->isLockedByServer($request)) {
                return $this->redirectToCreativeDesign($request);
            }

            return null;
        }

        $outcome = $this->verifyWithServer($request, false, false);

        if ($outcome === 'api_invalid' || $outcome === 'no_key') {
            return $this->redirectToCreativeDesign($request);
        }

        if ($outcome === 'valid' || $outcome === 'grace' || $outcome === 'unchanged') {
            return null;
        }

        return $this->redirectToCreativeDesign($request);
    }

    public function assertAdminLicenseValid(?Request $request = null): bool
    {
        return $this->enforceAdminLicense($request ?? request(), false) === null;
    }

    /**
     * @return string valid|no_key|api_invalid|grace|unchanged
     */
    public function verifyWithServer(?Request $request = null, bool $forceRecheck = false, bool $adminAlwaysApi = false): string
    {
        $request = $request ?? request();
        $domain = $this->currentDomain($request);
        $validatedKey = $this->cacheKey($request);
        $syncLockKey = '_lic_sync_lock_' . md5($domain);

        $this->ensureLicenseCredentials($request);

        if (!$this->hasLicenseCredentials()) {
            return 'no_key';
        }

        if (!$forceRecheck && !$adminAlwaysApi && $this->isValidatedCached($request)) {
            $this->pollServerSignalIfNeeded($request);

            if ($this->isLockedByServer($request)) {
                return 'api_invalid';
            }

            return 'unchanged';
        }

        $licenseKey = $this->getLicenseKey();

        try {
            $signature = hash_hmac('sha256', $domain, self::HMAC_SALT);

            $response = Http::withOptions(['verify' => false])
                ->timeout(10)
                ->post(self::VERIFY_URL, [
                    'domain'      => $domain,
                    'license_key' => $licenseKey,
                    'signature'   => $signature,
                    'url'         => $request->fullUrl(),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $status = strtolower((string) ($data['status'] ?? 'invalid'));
                $action = $data['action'] ?? 'none';
                $blocked = (bool) ($data['blocked'] ?? false);
                $message = (string) ($data['message'] ?? '');
                $signal = $data['cache_signal'] ?? null;
                $explicitValid = $data['valid'] ?? null;

                if ($action === 'wipe_out' || $blocked) {
                    $this->forgetValidatedLicenseCache($request);
                    Cache::forget($syncLockKey);
                    $this->lockFromServer($message ?: 'License blocked by server', $request);

                    return 'api_invalid';
                }

                $isValid = ($status === 'valid' || $explicitValid === true)
                    && $explicitValid !== false
                    && !in_array($status, ['invalid', 'expired', 'revoked', 'blocked', 'error', 'fail', 'failed'], true);

                if ($isValid) {
                    $this->unlockFromServer($request);
                    if (!empty($signal)) {
                        $signalKey = $this->signalCacheKey($request);
                        $lastSignal = Cache::get($signalKey);
                        if ($lastSignal && $lastSignal !== $signal) {
                            $this->clearAppDataCaches();
                        }
                        Cache::put($signalKey, $signal, now()->addHours(self::VALIDATED_CACHE_HOURS));
                    }
                    $this->rememberValidatedLicense($request, 'verified');

                    return 'valid';
                }

                $this->forgetValidatedLicenseCache($request);
                Cache::forget($syncLockKey);
                $reason = $message !== '' ? $message : 'Invalid license response from server';
                $this->lockFromServer($reason, $request);

                return 'api_invalid';
            }

            if ($response->serverError()) {
                if ($adminAlwaysApi && $this->isLockedByServer($request)) {
                    return 'api_invalid';
                }
                if ($this->isValidatedCached($request)) {
                    return 'unchanged';
                }
                $this->rememberValidatedLicense($request, 'grace', 12);

                return 'grace';
            }

            $this->forgetValidatedLicenseCache($request);
            Cache::forget($syncLockKey);
            $this->lockFromServer('Invalid license (HTTP ' . $response->status() . ')', $request);

            return 'api_invalid';
        } catch (\Exception $e) {
            if ($adminAlwaysApi && $this->isLockedByServer($request)) {
                return 'api_invalid';
            }
            if ($this->isValidatedCached($request)) {
                return 'unchanged';
            }

            $this->lockFromServer('Cannot reach license server', $request);

            return 'api_invalid';
        }
    }

    public function pollServerSignalIfNeeded(?Request $request = null): void
    {
        $request = $request ?? request();

        if (!$this->isValidatedCached($request)) {
            return;
        }

        $pollKey = $this->signalPollCacheKey($request);
        if (Cache::has($pollKey)) {
            return;
        }

        Cache::put($pollKey, 1, now()->addMinutes(30));
        $this->pollServerSignal($request);
    }

    public function pollServerSignal(?Request $request = null): void
    {
        if ($this->isMasterDomain($request)) {
            return;
        }

        $domain = $this->currentDomain($request);
        $validatedKey = $this->cacheKey($request);

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(5)
                ->get(self::SIGNAL_URL, ['domain' => $domain]);

            if (!$response->successful()) {
                return;
            }

            $data = $response->json();
            $action = $data['action'] ?? 'none';
            $blocked = (bool) ($data['blocked'] ?? false);
            $signal = $data['signal'] ?? null;

            if ($action === 'wipe_out' || $blocked) {
                $this->forgetValidatedLicenseCache($request);
                $this->lockFromServer('License revoked by server signal', $request);

                return;
            }

            if ($signal && $signal !== 'default') {
                $signalKey = $this->signalCacheKey($request);
                $lastSignal = Cache::get($signalKey);
                if ($lastSignal && $lastSignal !== $signal) {
                    $this->clearAppDataCaches();
                }
                Cache::put($signalKey, $signal, now()->addHours(24));
            }
        } catch (\Exception $e) {
            // ignore
        }
    }

    private function clearAppDataCaches(): void
    {
        $keys = [
            'pending_reviews_count', 'general_setting',
            'side_categories', 'menu_categories',
            'contact_info', 'social_icons',
            'pages_top', 'pages_right', 'common_menu',
            'brands_list', 'new_order_count', 'pending_orders_list',
            'order_status_list', 'pixels_list', 'gtm_code_list',
            'shurjopay_gateway_config',
        ];
        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (\Exception $e) {
                // ignore
            }
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    private function redirectToCreativeDesign(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'message' => 'License required.',
                'redirect' => $this->masterLicenseRedirectUrl($request),
            ], 403);
        }

        return redirect()->away($this->masterLicenseRedirectUrl($request));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    private function redirectToAdminLock(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'error',
                'message' => $this->getServerInvalidMessage($request) ?: 'Admin panel locked — invalid license.',
            ], 403);
        }

        return redirect()->route('admin.license.locked');
    }

    public function shouldBypassLicenseCheck(Request $request): bool
    {
        if ($request->routeIs('super.clear', 'admin.license.locked', 'admin.license.check', 'redx.webhook', 'steadfast.webhook')) {
            return true;
        }

        $path = ltrim($request->path(), '/');

        if ($path === 'up') {
            return true;
        }

        $exemptPrefixes = [
            'super/7575/clear',
            'admin/forgot-password',
            'admin/reset-password',
            'admin/license-locked',
            'admin/license-check',
            'password/reset',
            'password/email',
            'delivery/login',
            'delivery/forgot-password',
            'delivery/verify-otp',
            'delivery/resend-otp',
            'delivery/reset-password',
            'api/redx/webhook',
            'api/steadfast/webhook',
            'aamarpay/',
            'uddoktapay/',
            'bkash/checkout-url/callback',
        ];

        $exemptExact = [
            'payment-success',
            'payment-cancel',
        ];

        foreach ($exemptExact as $bp) {
            if ($path === $bp) {
                return true;
            }
        }

        foreach ($exemptPrefixes as $bp) {
            if ($path === rtrim($bp, '/') || str_starts_with($path, $bp)) {
                return true;
            }
        }

        return false;
    }

    public function isAdminPanelRequest(Request $request): bool
    {
        if ($this->shouldBypassLicenseCheck($request)) {
            return false;
        }

        $path = ltrim($request->path(), '/');

        $licenseBypassPaths = [
            'admin/forgot-password',
            'admin/reset-password',
            'admin/license-locked',
            'admin/license-check',
            'super/7575/clear',
            'password/reset',
            'password/email',
        ];

        foreach ($licenseBypassPaths as $bp) {
            if ($path === $bp || str_starts_with($path, rtrim($bp, '/') . '/')) {
                return false;
            }
        }

        if (str_starts_with($path, 'admin')) {
            return true;
        }

        foreach (['login', 'logout'] as $authPath) {
            if ($path === $authPath || str_starts_with($path, $authPath . '/')) {
                return true;
            }
        }

        if (str_starts_with($path, 'super/7575')) {
            return true;
        }

        return false;
    }
}
