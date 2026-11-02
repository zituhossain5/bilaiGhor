<?php

namespace App\Services;

use App\Models\BackupLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DriveBackupRunner
{
    /** @return array{log: BackupLog, warning: ?string} */
    public function run(?int $adminId = null): array
    {
        if (! config('backups.drive.enabled')) {
            throw new RuntimeException('Automatic Google Drive backups are disabled');
        }

        $lock = Cache::lock('admin-database-backup-generation', 1800);
        if (! $lock->get()) {
            throw new RuntimeException('A database backup is already running');
        }

        $log = null;
        $compressed = null;
        $upload = null;
        $stage = 'dump';
        try {
            $log = BackupLog::create([
                'type' => 'database',
                'destination' => 'google_drive',
                'filename' => 'bilaighor-db-'.now()->format('Y-m-d-H-i-s').'.sql.gz',
                'status' => 'running',
                'started_at' => now(),
                'created_by' => $adminId,
            ]);
            Log::info('Drive database backup started', ['backup_log_id' => $log->id]);

            if (config('backups.encryption.enabled')) {
                $stage = 'encryption';
                app(BackupEncryptionService::class)->assertConfigured();
                $stage = 'dump';
            }

            $stage = 'configuration';
            $drive = app(GoogleDriveBackupService::class);
            if (! $drive->configured()) {
                throw new RuntimeException('Drive backup configuration is incomplete');
            }
            $stage = 'dump';

            $compressed = app(DatabaseBackupService::class)->generate(true);
            $log->update(['filename' => $compressed['filename']]);
            Log::info('Drive database dump and compression complete', ['backup_log_id' => $log->id]);

            $upload = $compressed;
            if (config('backups.encryption.enabled')) {
                $stage = 'encryption';
                $upload = app(BackupEncryptionService::class)->encrypt($compressed);
                if (! @unlink($compressed['path'])) {
                    throw new RuntimeException('Unencrypted temporary backup could not be removed');
                }
                Log::info('Drive database backup encrypted', ['backup_log_id' => $log->id]);
            }

            $stage = 'upload';
            $remote = $drive->uploadAndVerify(
                $upload['path'], $upload['filename'], $upload['size']
            );
            $log->update([
                'filename' => $upload['filename'],
                'size_bytes' => $remote['size'],
                'drive_file_id' => $remote['id'],
                'encrypted' => (bool) config('backups.encryption.enabled'),
                'status' => 'success',
                'completed_at' => now(),
            ]);
            Log::info('Drive database backup uploaded and verified', ['backup_log_id' => $log->id]);

            $warnings = [];
            if (! @unlink($upload['path'])) {
                $warnings[] = 'Local temporary backup could not be removed.';
                Log::warning('Drive backup local cleanup failed', ['backup_log_id' => $log->id]);
            }

            try {
                $drive->pruneOldBackups(
                    (int) config('backups.retention_days'), $remote['id']
                );
                Log::info('Drive database backup retention complete', ['backup_log_id' => $log->id]);
            } catch (Throwable $e) {
                $warnings[] = 'Retention cleanup failed.';
                Log::warning('Drive backup retention failed', [
                    'backup_log_id' => $log->id, 'exception' => get_class($e),
                ]);
            }

            if ($warnings !== []) $log->update(['warning_message' => implode(' ', $warnings)]);

            return ['log' => $log->fresh(), 'warning' => $warnings !== [] ? implode(' ', $warnings) : null];
        } catch (Throwable $e) {
            $safe = $stage === 'configuration' ? 'Google Drive backup is not configured.' : (
                $stage === 'dump' ? 'Database dump failed.' : (
                $stage === 'encryption' ? 'Backup encryption failed.' : (
                    $e->getMessage() === 'Drive storage quota exceeded'
                        ? 'Google Drive storage quota exceeded.' : 'Google Drive upload or verification failed.'
                )
            ));
            Log::error('Drive database backup failed', [
                'backup_log_id' => $log?->id, 'stage' => $stage,
                'exception' => get_class($e), 'category' => $safe,
            ]);
            if ($log !== null) {
                $log->update([
                    'status' => 'failed',
                    'filename' => $upload['filename'] ?? $compressed['filename'] ?? $log->filename,
                    'size_bytes' => $upload['size'] ?? $compressed['size'] ?? null,
                    'encrypted' => $upload !== null && str_ends_with($upload['filename'], '.enc'),
                    'error_message' => $safe,
                    'completed_at' => now(),
                ]);
            }
            // Preserve the private compressed/encrypted file for a short retry window.
            throw new RuntimeException($safe, previous: $e);
        } finally {
            $lock->release();
        }
    }
}
