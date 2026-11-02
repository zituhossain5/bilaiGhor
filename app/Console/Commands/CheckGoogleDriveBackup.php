<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveBackupService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class CheckGoogleDriveBackup extends Command
{
    protected $signature = 'backup:drive-check';
    protected $description = 'Check private Google Drive backup folder access without creating a backup';

    public function handle(GoogleDriveBackupService $drive): int
    {
        if (! $drive->configured()) {
            $this->error('Google Drive backup folder or credential file is not configured.');
            return self::FAILURE;
        }

        try {
            $drive->checkAccess();
            $this->info('Google Drive backup folder is accessible, inside a Shared Drive, and writable.');
            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $safe = [
                'Backup folder not found or not shared with the service account.',
                'Configured Google Drive ID is not an active folder.',
                'Backup folder is in My Drive; use a Google Workspace Shared Drive.',
                'Service account cannot add files to the backup folder.',
                'Service account lacks write permission in Google Drive.',
                'Google Drive API is not enabled for this project.',
                'Google Drive authentication failed.',
                'Google Drive service account credentials are invalid.',
                'Google Drive service account key could not sign the request.',
                'Google Drive storage quota or item limit exceeded.',
                'Google Drive denied access; check service account folder permissions and API access.',
            ];
            $this->error(in_array($e->getMessage(), $safe, true) ? $e->getMessage() : 'Google Drive folder check failed.');
            return self::FAILURE;
        } catch (Throwable) {
            $this->error('Google Drive folder check failed.');
            return self::FAILURE;
        }
    }
}
