<?php

namespace App\Console\Commands;

use App\Services\DriveBackupRunner;
use Illuminate\Console\Command;
use RuntimeException;

class BackupDatabaseToDrive extends Command
{
    protected $signature = 'backup:database-to-drive';
    protected $description = 'Create and verify a private database backup in Google Drive';

    public function handle(DriveBackupRunner $runner): int
    {
        try {
            $result = $runner->run();
            $this->info($result['warning']
                ? 'Backup uploaded successfully, but '.$result['warning']
                : 'Backup completed and uploaded to Google Drive.');
            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
