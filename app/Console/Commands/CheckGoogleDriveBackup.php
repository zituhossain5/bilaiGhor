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
            $this->error('Connect Google Drive and configure the backup folder first.');
            return self::FAILURE;
        }

        try {
            $drive->checkAccess(true);
            $this->info('Google Drive backup folder is accessible and writable in My Drive.');
            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $safe = [
                'Connect Google Drive before creating an off-server backup.',
                'Google Drive authorization expired. Reconnect Google Drive from Backup Management.',
                'Google Drive token refresh failed.',
                'Google Drive access token was rejected. Retry the connection test.',
                'Backup folder is unavailable to this app. Clear the configured folder ID or disconnect and reconnect to create a new folder.',
                'Configured Google Drive ID is not an active folder.',
                'Google Drive cannot add files to the backup folder.',
                'Google Drive lacks write permission for the backup folder.',
                'Google Drive API is not enabled for this project.',
                'Google Drive storage quota or item limit exceeded.',
                'Google Drive denied access to the backup folder.',
            ];
            $this->error(in_array($e->getMessage(), $safe, true) ? $e->getMessage() : 'Google Drive folder check failed.');
            return self::FAILURE;
        } catch (Throwable) {
            $this->error('Google Drive folder check failed.');
            return self::FAILURE;
        }
    }
}
