<?php

namespace App\Services;

use App\Models\GoogleDriveBackupConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveBackupService
{
    private const FILES_URL = 'https://www.googleapis.com/drive/v3/files';
    private const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';
    private ?string $accessToken = null;

    public function configured(): bool
    {
        try {
            if (! app(GoogleDriveOAuthService::class)->configured()
                || ! GoogleDriveBackupConnection::find(1)?->isConnected()) return false;
            $this->folderId();
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /** @return array{folder_id:string,email:?string} */
    public function prepareConnection(string $accessToken): array
    {
        $folder = (string) (config('backups.drive.folder_id') ?: GoogleDriveBackupConnection::find(1)?->folder_id);
        if ($folder === '') {
            // With drive.file, the app can manage folders it creates. A folder made
            // manually in My Drive is not granted to the app by its ID alone.
            $response = Http::withToken($accessToken)->timeout(30)->post(self::FILES_URL.'?fields=id,name,mimeType,trashed', [
                'name' => 'Bilai Ghor Backups',
                'mimeType' => 'application/vnd.google-apps.folder',
                'appProperties' => ['bilaighor_backup' => 'folder'],
            ]);
            if (! $response->successful()) $this->failForResponse($response->status(), $response->json());
            $folder = (string) $response->json('id');
        }
        $this->assertFolder($accessToken, $folder);

        $about = Http::withToken($accessToken)->timeout(30)->get('https://www.googleapis.com/drive/v3/about', [
            'fields' => 'user(emailAddress)',
        ]);
        if (! $about->successful()) $this->failForResponse($about->status(), $about->json());
        $email = $about->json('user.emailAddress');
        return ['folder_id' => $folder, 'email' => is_string($email) ? $email : null];
    }

    /** Verify the folder; optionally create and remove a disposable file. */
    public function checkAccess(bool $probe = false): void
    {
        $token = $this->token();
        $folder = $this->folderId();
        $this->assertFolder($token, $folder);
        if (! $probe) return;

        $create = Http::withToken($token)->timeout(30)->post(self::FILES_URL.'?fields=id,parents,trashed', [
            'name' => 'bilaighor-backup-connection-test',
            'mimeType' => 'text/plain',
            'parents' => [$folder],
            'appProperties' => ['bilaighor_backup_test' => '1'],
        ]);
        if (! $create->successful()) $this->failForResponse($create->status(), $create->json());
        $id = $create->json('id');
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new RuntimeException('Google Drive test file could not be verified.');
        }
        $valid = false;
        try {
            $file = $this->metadata($id);
            $valid = ($file['id'] ?? null) === $id
                && in_array($folder, $file['parents'] ?? [], true)
                && ($file['trashed'] ?? true) === false;
        } finally {
            $deleted = Http::withToken($token)->timeout(30)->delete(self::FILES_URL.'/'.$id);
            if (! $deleted->successful()) throw new RuntimeException('Google Drive test file could not be removed.');
        }
        if (! $valid) throw new RuntimeException('Google Drive test file could not be verified.');
    }

    private function assertFolder(string $token, string $folder): void
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $folder)) {
            throw new RuntimeException('Google Drive backup folder is not configured.');
        }
        $response = Http::withToken($token)->timeout(30)->get(self::FILES_URL.'/'.$folder, [
            'fields' => 'id,mimeType,trashed,capabilities(canAddChildren)',
        ]);
        if ($response->status() === 404) {
            throw new RuntimeException('Backup folder is unavailable to this app. Clear the configured folder ID or disconnect and reconnect to create a new folder.');
        }
        if (! $response->successful()) $this->failForResponse($response->status(), $response->json());

        $folderInfo = $response->json();
        if (($folderInfo['id'] ?? null) !== $folder || ($folderInfo['trashed'] ?? true) !== false
            || ($folderInfo['mimeType'] ?? null) !== 'application/vnd.google-apps.folder') {
            throw new RuntimeException('Configured Google Drive ID is not an active folder.');
        }
        if (($folderInfo['capabilities']['canAddChildren'] ?? false) !== true) {
            throw new RuntimeException('Google Drive cannot add files to the backup folder.');
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
        $id = (string) (GoogleDriveBackupConnection::find(1)?->folder_id ?: config('backups.drive.folder_id'));
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new RuntimeException('Drive backup folder is not configured');
        }
        return $id;
    }

    private function token(): string
    {
        if ($this->accessToken !== null) return $this->accessToken;
        return $this->accessToken = app(GoogleDriveOAuthService::class)->accessToken();
    }

    private function failForResponse(int $status, mixed $body): never
    {
        $error = is_array($body) ? ($body['error'] ?? null) : null;
        $reason = is_array($error) ? ($error['errors'][0]['reason'] ?? '') : '';
        if (in_array($reason, ['storageQuotaExceeded', 'teamDriveFileLimitExceeded'], true)) {
            throw new RuntimeException('Google Drive storage quota or item limit exceeded.');
        }
        if ($reason === 'insufficientFilePermissions') {
            throw new RuntimeException('Google Drive lacks write permission for the backup folder.');
        }
        if ($reason === 'accessNotConfigured') {
            throw new RuntimeException('Google Drive API is not enabled for this project.');
        }
        if ($status === 401) {
            $this->accessToken = null;
            GoogleDriveBackupConnection::find(1)?->update(['access_token' => null, 'token_expires_at' => null]);
            throw new RuntimeException('Google Drive access token was rejected. Retry the connection test.');
        }
        if ($status === 403) {
            throw new RuntimeException('Google Drive denied access to the backup folder.');
        }
        throw new RuntimeException('Drive API request failed (HTTP '.$status.')');
    }
}
