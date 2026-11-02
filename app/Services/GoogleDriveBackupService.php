<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveBackupService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const FILES_URL = 'https://www.googleapis.com/drive/v3/files';
    private const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';
    private ?string $accessToken = null;

    public function configured(): bool
    {
        try {
            $this->folderId();
            $this->credentialsPath();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /** Check access without creating a backup or modifying Drive. */
    public function checkAccess(): void
    {
        $folder = $this->folderId();
        $response = Http::withToken($this->token())->timeout(30)->get(self::FILES_URL.'/'.$folder, [
            'fields' => 'id,mimeType,driveId,trashed,capabilities(canAddChildren)',
            'supportsAllDrives' => 'true',
        ]);
        if ($response->status() === 404) {
            throw new RuntimeException('Backup folder not found or not shared with the service account.');
        }
        if (! $response->successful()) {
            $this->failForResponse($response->status(), $response->json());
        }

        $folderInfo = $response->json();
        if (($folderInfo['id'] ?? null) !== $folder || ($folderInfo['trashed'] ?? true) !== false
            || ($folderInfo['mimeType'] ?? null) !== 'application/vnd.google-apps.folder') {
            throw new RuntimeException('Configured Google Drive ID is not an active folder.');
        }
        if (empty($folderInfo['driveId'])) {
            throw new RuntimeException('Backup folder is in My Drive; use a Google Workspace Shared Drive.');
        }
        if (($folderInfo['capabilities']['canAddChildren'] ?? false) !== true) {
            throw new RuntimeException('Service account cannot add files to the backup folder.');
        }
    }

    /** @return array{id: string, size: int, created_at: string} */
    public function uploadAndVerify(string $path, string $filename, int $size): array
    {
        $folder = $this->folderId();
        if (! is_file($path) || filesize($path) !== $size) {
            throw new RuntimeException('Local backup file is unavailable');
        }

        $session = Http::withToken($this->token())->timeout(30)->withHeaders([
            'X-Upload-Content-Type' => 'application/octet-stream',
            'X-Upload-Content-Length' => (string) $size,
        ])->post(self::UPLOAD_URL.'?uploadType=resumable&supportsAllDrives=true&fields=id,name,size,createdTime,parents,trashed', [
            'name' => $filename,
            'parents' => [$folder],
            'mimeType' => 'application/octet-stream',
            'appProperties' => ['bilaighor_backup' => 'database'],
        ]);
        if (! $session->successful()) $this->failForResponse($session->status(), $session->json());

        $location = $session->header('Location');
        if (! is_string($location) || parse_url($location, PHP_URL_SCHEME) !== 'https'
            || parse_url($location, PHP_URL_HOST) !== 'www.googleapis.com') {
            throw new RuntimeException('Drive upload session was invalid');
        }

        $stream = fopen($path, 'rb');
        if ($stream === false) throw new RuntimeException('Local backup file is unavailable');
        try {
            $uploaded = Http::withToken($this->token())->timeout(900)
                ->withHeaders(['Content-Length' => (string) $size])
                ->withBody($stream, 'application/octet-stream')->put($location);
        } finally {
            fclose($stream);
        }
        if (! $uploaded->successful()) $this->failForResponse($uploaded->status(), $uploaded->json());

        $id = $uploaded->json('id');
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new RuntimeException('Drive upload returned no valid file ID');
        }

        $verified = $this->metadata($id);
        if (($verified['id'] ?? null) !== $id || ($verified['name'] ?? null) !== $filename
            || (string) ($verified['size'] ?? '') !== (string) $size
            || ! in_array($folder, $verified['parents'] ?? [], true)
            || ($verified['trashed'] ?? true) !== false) {
            throw new RuntimeException('Drive upload verification failed');
        }

        return ['id' => $id, 'size' => $size, 'created_at' => (string) ($verified['createdTime'] ?? '')];
    }

    /** Delete only marked Bilai Ghor database backups older than the configured period. */
    public function pruneOldBackups(int $days, ?string $excludeId = null): int
    {
        $folder = $this->folderId();
        $cutoff = now()->subDays(max(1, $days));
        $deleted = 0;
        $page = null;

        do {
            $query = [
                'q' => "'{$folder}' in parents and trashed = false",
                'fields' => 'nextPageToken,files(id,name,createdTime,parents,trashed,appProperties)',
                'pageSize' => 1000,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ];
            if ($page !== null) $query['pageToken'] = $page;
            $response = Http::withToken($this->token())->timeout(30)->get(self::FILES_URL, $query);
            if (! $response->successful()) $this->failForResponse($response->status(), $response->json());

            foreach ($response->json('files', []) as $file) {
                if (! $this->isOurOldBackup($file, $folder, $cutoff, $excludeId)) continue;
                $fresh = $this->metadata($file['id']);
                if (! $this->isOurOldBackup($fresh, $folder, $cutoff, $excludeId)) continue;

                $delete = Http::withToken($this->token())->timeout(30)
                    ->delete(self::FILES_URL.'/'.$file['id'].'?supportsAllDrives=true');
                if (! $delete->successful()) $this->failForResponse($delete->status(), $delete->json());
                $deleted++;
            }
            $page = $response->json('nextPageToken');
        } while (is_string($page) && $page !== '');

        return $deleted;
    }

    private function isOurOldBackup(array $file, string $folder, Carbon $cutoff, ?string $excludeId): bool
    {
        $id = $file['id'] ?? null;
        $name = $file['name'] ?? null;
        $created = $file['createdTime'] ?? null;
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]+$/', $id)
            && $id !== $excludeId
            && is_string($name)
            && preg_match('/^bilaighor-db-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.sql\.gz(?:\.enc)?$/', $name)
            && ($file['appProperties']['bilaighor_backup'] ?? null) === 'database'
            && in_array($folder, $file['parents'] ?? [], true)
            && ($file['trashed'] ?? true) === false
            && is_string($created) && Carbon::parse($created)->lessThan($cutoff);
    }

    private function metadata(string $id): array
    {
        $response = Http::withToken($this->token())->timeout(30)->get(self::FILES_URL.'/'.$id, [
            'fields' => 'id,name,size,createdTime,parents,trashed,appProperties',
            'supportsAllDrives' => 'true',
        ]);
        if (! $response->successful()) $this->failForResponse($response->status(), $response->json());
        return $response->json() ?: [];
    }

    private function folderId(): string
    {
        $id = (string) config('backups.drive.folder_id');
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new RuntimeException('Drive backup folder is not configured');
        }
        return $id;
    }

    private function token(): string
    {
        if ($this->accessToken !== null) return $this->accessToken;

        $real = $this->credentialsPath();
        $credentials = json_decode(file_get_contents($real), true);
        if (! is_array($credentials) || ($credentials['type'] ?? null) !== 'service_account'
            || ! is_string($credentials['client_email'] ?? null)
            || ! is_string($credentials['private_key'] ?? null)) {
            throw new RuntimeException('Google Drive service account credentials are invalid.');
        }

        $issued = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64Url(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => self::TOKEN_URL,
            'iat' => $issued,
            'exp' => $issued + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$claims;
        $key = openssl_pkey_get_private($credentials['private_key']);
        if ($key === false || ! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Google Drive service account key could not sign the request.');
        }
        $assertion = $unsigned.'.'.$this->base64Url($signature);
        $response = Http::asForm()->timeout(30)->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);
        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new RuntimeException('Google Drive authentication failed.');
        }
        return $this->accessToken = $response->json('access_token');
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function credentialsPath(): string
    {
        $path = (string) config('backups.drive.credentials');
        $real = $path !== '' ? realpath($path) : false;
        $project = realpath(base_path());
        if ($real === false || ! is_file($real) || ! is_readable($real)
            || ($project !== false && str_starts_with(strtolower($real), strtolower($project.DIRECTORY_SEPARATOR)))) {
            throw new RuntimeException('Drive credentials are unavailable or inside the application directory');
        }
        return $real;
    }

    private function failForResponse(int $status, mixed $body): never
    {
        $reason = is_array($body) ? ($body['error']['errors'][0]['reason'] ?? '') : '';
        if (in_array($reason, ['storageQuotaExceeded', 'teamDriveFileLimitExceeded'], true)) {
            throw new RuntimeException('Google Drive storage quota or item limit exceeded.');
        }
        if ($reason === 'insufficientFilePermissions') {
            throw new RuntimeException('Service account lacks write permission in Google Drive.');
        }
        if ($reason === 'accessNotConfigured') {
            throw new RuntimeException('Google Drive API is not enabled for this project.');
        }
        if ($status === 401) {
            throw new RuntimeException('Google Drive authentication failed.');
        }
        if ($status === 403) {
            throw new RuntimeException('Google Drive denied access; check service account folder permissions and API access.');
        }
        throw new RuntimeException('Drive API request failed (HTTP '.$status.')');
    }
}
