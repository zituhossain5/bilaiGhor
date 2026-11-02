<?php

namespace App\Services;

use App\Models\GoogleDriveBackupConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveOAuthService
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function configured(): bool
    {
        try {
            $this->clientId();
            $this->clientSecret();
            $this->redirectUri();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function authorizationUrl(string $state, bool $forceConsent): string
    {
        $params = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'state' => $state,
        ];
        if ($forceConsent) $params['prompt'] = 'consent';

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array{access_token:string,refresh_token:string,expires_at:\Illuminate\Support\Carbon,scope:string} */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);
        if (! $response->successful()) throw new RuntimeException('Google authorization could not be completed.');

        $access = $response->json('access_token');
        $refresh = $response->json('refresh_token');
        $scope = $response->json('scope', self::SCOPE);
        $seconds = $response->json('expires_in');
        if (! is_string($access) || $access === '' || ! is_string($refresh) || $refresh === ''
            || ! is_numeric($seconds) || ! in_array(self::SCOPE, explode(' ', (string) $scope), true)) {
            throw new RuntimeException('Google did not return offline Drive access. Reconnect and approve access.');
        }

        return [
            'access_token' => $access,
            'refresh_token' => $refresh,
            'expires_at' => now()->addSeconds(max(60, (int) $seconds)),
            'scope' => (string) $scope,
        ];
    }

    public function saveConnection(array $tokens, string $folderId, ?string $email): GoogleDriveBackupConnection
    {
        $connection = GoogleDriveBackupConnection::firstOrNew(['id' => 1]);
        $connection->fill([
            'google_email' => $email,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_expires_at' => $tokens['expires_at'],
            'scope' => $tokens['scope'],
            'folder_id' => $folderId,
            'requires_reauth' => false,
            'connected_at' => now(),
        ]);
        $connection->save();
        return $connection;
    }

    public function accessToken(): string
    {
        $connection = GoogleDriveBackupConnection::find(1);
        if (! $connection || ! $connection->isConnected()) {
            throw new RuntimeException('Connect Google Drive before creating an off-server backup.');
        }
        if (filled($connection->access_token)
            && $connection->token_expires_at?->greaterThan(now()->addMinute())) {
            return $connection->access_token;
        }

        $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ]);
        if (! $response->successful()) {
            if ($response->json('error') === 'invalid_grant') {
                $connection->update([
                    'access_token' => null, 'refresh_token' => null,
                    'token_expires_at' => null, 'requires_reauth' => true,
                ]);
                throw new RuntimeException('Google Drive authorization expired. Reconnect Google Drive from Backup Management.');
            }
            throw new RuntimeException('Google Drive token refresh failed.');
        }

        $access = $response->json('access_token');
        $seconds = $response->json('expires_in');
        if (! is_string($access) || $access === '' || ! is_numeric($seconds)) {
            throw new RuntimeException('Google Drive token refresh failed.');
        }
        $updates = [
            'access_token' => $access,
            'token_expires_at' => now()->addSeconds(max(60, (int) $seconds)),
        ];
        $rotatedRefresh = $response->json('refresh_token');
        if (is_string($rotatedRefresh) && $rotatedRefresh !== '') {
            $updates['refresh_token'] = $rotatedRefresh;
        }
        $connection->update($updates);
        return $access;
    }

    private function clientId(): string
    {
        $value = (string) config('backups.drive.client_id');
        if ($value === '') throw new RuntimeException('Google Drive OAuth client is not configured.');
        return $value;
    }

    private function clientSecret(): string
    {
        $value = (string) config('backups.drive.client_secret');
        if ($value === '') throw new RuntimeException('Google Drive OAuth client is not configured.');
        return $value;
    }

    private function redirectUri(): string
    {
        $uri = (string) config('backups.drive.redirect_uri');
        if (parse_url($uri, PHP_URL_SCHEME) !== 'https'
            || parse_url($uri, PHP_URL_PATH) !== '/admin/backups/google/callback') {
            throw new RuntimeException('Google Drive OAuth callback is not configured for HTTPS.');
        }
        return $uri;
    }
}
