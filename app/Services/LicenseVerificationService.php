<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * License checks are retired: the script is owned outright and the vendor's license
 * server (bmitltd.com) is no longer used. This class stays only so any remaining caller —
 * including the ionCube-encoded controllers, which cannot be inspected — still resolves.
 *
 * Every method answers "licensed" offline: no network calls, no .env writes, no admin locks.
 */
class LicenseVerificationService
{
    // Kept for backward compatibility with code that may reference them.
    public const MASTER_DOMAIN = 'bmitltd.com';
    public const VERIFY_URL = 'https://www.bmitltd.com/api/verify-license';
    public const MASTER_LICENSE_PAGE = 'https://www.bmitltd.com/license';
    public const SIGNAL_URL = 'https://www.bmitltd.com/api/cache-signal';
    public const FETCH_URL = 'https://www.bmitltd.com/api/fetch-license';
    public const HMAC_SALT = '';
    public const VALIDATED_CACHE_HOURS = 100;

    public function currentDomain(?Request $request = null): string
    {
        $request = $request ?? request();

        return strtolower(str_replace(['www.', 'http://', 'https://'], '', $request->getHost()));
    }

    public function isMasterDomain(?Request $request = null): bool
    {
        return false;
    }

    public function cacheKey(?Request $request = null): string
    {
        return '_license_retired';
    }

    public function signalCacheKey(?Request $request = null): string
    {
        return '_license_retired_signal';
    }

    public function signalPollCacheKey(?Request $request = null): string
    {
        return '_license_retired_poll';
    }

    public function cacheMetaKey(?Request $request = null): string
    {
        return '_license_retired_meta';
    }

    public function getLicenseKey(): ?string
    {
        return null;
    }

    public function getLicenseSignature(): ?string
    {
        return null;
    }

    public function hasLicenseCredentials(): bool
    {
        return true;
    }

    public function isValidatedCached(?Request $request = null): bool
    {
        return true;
    }

    public function rememberValidatedLicense(?Request $request = null, string $state = 'verified', ?int $hours = null): void
    {
    }

    public function forgetValidatedLicenseCache(?Request $request = null): void
    {
    }

    public function getCacheStatus(?Request $request = null): array
    {
        return [
            'active'             => true,
            'state'              => 'retired',
            'cache_key'          => $this->cacheKey($request),
            'hours_configured'   => self::VALIDATED_CACHE_HOURS,
            'cached_at'          => null,
            'expires_at'         => null,
            'remaining_label'    => null,
            'api_skipped'        => true,
            'admin_locked'       => false,
            'admin_lock_message' => null,
            'has_credentials'    => true,
            'driver'             => config('cache.default'),
        ];
    }

    public function bootstrapValidatedCache(?Request $request = null): void
    {
    }

    /** Never touches .env any more — kept so old callers still resolve. */
    public function readEnvValue(string $key): ?string
    {
        return null;
    }

    public function writeEnvValues(array $values): bool
    {
        return false;
    }

    public function clearConfigCache(): void
    {
    }

    public function ensureLicenseCredentials(?Request $request = null): bool
    {
        return true;
    }

    public function autoSyncFromMaster(?Request $request = null): bool
    {
        return true;
    }

    public function adminLockCacheKey(?Request $request = null): string
    {
        return '_license_retired_lock';
    }

    public function serverInvalidMessageKey(?Request $request = null): string
    {
        return '_license_retired_msg';
    }

    public function isLockedByServer(?Request $request = null): bool
    {
        return false;
    }

    public function getServerInvalidMessage(?Request $request = null): ?string
    {
        return null;
    }

    public function lockFromServer(string $reason = '', ?Request $request = null): void
    {
    }

    public function unlockFromServer(?Request $request = null): void
    {
    }

    public function masterLicenseRedirectUrl(?Request $request = null, ?string $domain = null): string
    {
        return url('/');
    }

    public function clearAllLicenseCaches(?Request $request = null): void
    {
    }

    /** @return null Never redirects. */
    public function enforceFreshLicenseCheck(Request $request)
    {
        return null;
    }

    /** @return null Never redirects. */
    public function redirectIfMissingLicense(Request $request)
    {
        return null;
    }

    /** @return null Never redirects. */
    public function enforceAdminLicense(Request $request, bool $creativeDesignOnFailure = false)
    {
        return null;
    }

    public function assertAdminLicenseValid(?Request $request = null): bool
    {
        return true;
    }

    /** @return string Always "valid". */
    public function verifyWithServer(?Request $request = null, bool $forceRecheck = false, bool $adminAlwaysApi = false): string
    {
        return 'valid';
    }

    public function pollServerSignalIfNeeded(?Request $request = null): void
    {
    }

    public function pollServerSignal(?Request $request = null): void
    {
    }

    public function shouldBypassLicenseCheck(Request $request): bool
    {
        return true;
    }

    public function isAdminPanelRequest(Request $request): bool
    {
        return false;
    }
}
