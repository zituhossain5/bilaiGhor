<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Creative Design license verify — AppServiceProvider (Layer 1) এর সাথে একই নিয়ম।
 */
class CreativeDesignLicenseVerifier
{
    public const MASTER_DOMAIN = 'bmitltd.com';

    public const VERIFY_URL = 'https://www.bmitltd.com/api/verify-license';

    public const HMAC_SALT = 'your_secret_salt_key';

    public static function resolveDomain(?string $host = null): string
    {
        $domain = strtolower(str_replace('www.', '', $host ?? request()->getHost()));

        if (in_array($domain, ['127.0.0.1', '::1'], true)) {
            $appHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST);
            if ($appHost) {
                return strtolower(str_replace('www.', '', $appHost));
            }

            return 'localhost';
        }

        return $domain;
    }

    public static function readLicenseKey(): string
    {
        return self::readEnvValue('LICENSE_KEY');
    }

    public static function readLicenseSignature(): string
    {
        return self::readEnvValue('LICENSE_SIGNATURE');
    }

    private static function readEnvValue(string $key): string
    {
        $path = base_path('.env');
        if (is_readable($path)) {
            $content = file_get_contents($path);
            $pattern = '/^' . preg_quote($key, '/') . '\s*=\s*(.*)$/m';
            if (preg_match($pattern, $content, $m)) {
                return trim($m[1], " \t\n\r\0\x0B\"'");
            }
        }

        return trim((string) env($key, ''));
    }

    /**
     * Script Update API (check-update / download-update) payload.
     *
     * @return array{domain: string, license_key: string, signature: string, script_name: string, current_version: string}
     */
    public static function scriptUpdatePayload(string $scriptName, string $currentVersion): array
    {
        return [
            'domain'          => self::resolveDomain(),
            'license_key'     => self::readLicenseKey(),
            'signature'       => self::readLicenseSignature(),
            'script_name'     => $scriptName,
            'current_version' => $currentVersion ?: '1.0.0',
        ];
    }

    public static function validateScriptUpdatePayload(array $payload): ?string
    {
        if (empty($payload['domain'])) {
            return 'domain প্রয়োজন';
        }
        if (empty($payload['license_key'])) {
            return 'LICENSE_KEY .env-এ নেই';
        }
        if (empty($payload['signature'])) {
            return 'LICENSE_SIGNATURE .env-এ নেই (check-update API-তে বাধ্যতামূলক)';
        }
        if (empty($payload['script_name'])) {
            return 'script_name প্রয়োজন';
        }

        return null;
    }

    /**
     * POST form-urlencoded — Creative Design script-update API format.
     */
    public static function postScriptUpdateApi(string $url, array $payload, int $timeoutSeconds = 60): \Illuminate\Http\Client\Response
    {
        return Http::withOptions(['verify' => false])
            ->timeout($timeoutSeconds)
            ->acceptJson()
            ->asForm()
            ->post($url, $payload);
    }

    public static function isLocalDomain(string $domain): bool
    {
        return in_array($domain, ['localhost', '127.0.0.1', '::1'], true);
    }

    public static function isExempt(string $domain): bool
    {
        return $domain === self::MASTER_DOMAIN || self::isLocalDomain($domain);
    }

    public static function domainsMatch(string $current, string $registered): bool
    {
        $a = strtolower(trim(str_replace('www.', '', $current)));
        $b = strtolower(trim(str_replace('www.', '', $registered)));

        return $a !== '' && $a === $b;
    }

    public static function extractRegisteredDomain(array $data): ?string
    {
        foreach (['domain_name', 'domain', 'registered_domain'] as $key) {
            if (!empty($data[$key])) {
                return str_replace('www.', '', (string) $data[$key]);
            }
        }

        return null;
    }

    public static function validatedCacheKey(string $licenseKey): string
    {
        return '_session_validator_v3_' . substr(md5($licenseKey ?: ''), 0, 8);
    }

    /**
     * @return array{valid: bool, message: string, data: ?array, domain: string}
     */
    public static function verify(bool $useCache = true): array
    {
        $domain = self::resolveDomain();

        if (self::isExempt($domain)) {
            return [
                'valid'   => true,
                'message' => 'Master বা local domain — লাইসেন্স চেক স্কিপ',
                'data'    => null,
                'domain'  => $domain,
            ];
        }

        $licenseKey = self::readLicenseKey();
        if ($licenseKey === '') {
            return [
                'valid'   => false,
                'message' => 'LICENSE_KEY .env ফাইলে পাওয়া যায়নি',
                'data'    => null,
                'domain'  => $domain,
            ];
        }

        $cacheKey = self::validatedCacheKey($licenseKey);
        if ($useCache && Cache::has($cacheKey)) {
            return [
                'valid'   => true,
                'message' => 'লাইসেন্স যাচাই হয়েছে (ক্যাশ)',
                'data'    => null,
                'domain'  => $domain,
            ];
        }

        try {
            $signature = hash_hmac('sha256', $domain, self::HMAC_SALT);

            $response = Http::withOptions(['verify' => false])
                ->timeout(12)
                ->acceptJson()
                ->asJson()
                ->post(self::VERIFY_URL, [
                    'domain'      => $domain,
                    'license_key' => $licenseKey,
                    'signature'   => $signature,
                    'url'         => request()->fullUrl(),
                ]);

            if (!$response->successful()) {
                $msg = $response->json('message') ?? ('License API HTTP ' . $response->status());

                return [
                    'valid'   => false,
                    'message' => $msg,
                    'data'    => $response->json(),
                    'domain'  => $domain,
                ];
            }

            $data   = $response->json() ?? [];
            $status = $data['status'] ?? 'invalid';

            if (($data['action'] ?? '') === 'wipe_out' || !empty($data['blocked'])) {
                return [
                    'valid'   => false,
                    'message' => $data['message'] ?? 'License blocked',
                    'data'    => $data,
                    'domain'  => $domain,
                ];
            }

            if ($status !== 'valid') {
                return [
                    'valid'   => false,
                    'message' => $data['message'] ?? 'লাইসেন্স কী বা ডোমেইন সঠিক নয়',
                    'data'    => $data,
                    'domain'  => $domain,
                ];
            }

            $registered = self::extractRegisteredDomain($data);
            if ($registered !== null && !self::domainsMatch($domain, $registered)) {
                return [
                    'valid'   => false,
                    'message' => 'ডোমেইন মিলছে না। লাইসেন্স: ' . $registered . ' | সাইট: ' . $domain,
                    'data'    => $data,
                    'domain'  => $domain,
                ];
            }

            Cache::put($cacheKey, 'verified', now()->addHours(100));

            return [
                'valid'   => true,
                'message' => 'লাইসেন্স ও ডোমেইন সঠিক',
                'data'    => $data,
                'domain'  => $domain,
            ];
        } catch (\Throwable $e) {
            return [
                'valid'   => false,
                'message' => 'License API সংযোগ ব্যর্থ: ' . $e->getMessage(),
                'data'    => null,
                'domain'  => $domain,
            ];
        }
    }
}
