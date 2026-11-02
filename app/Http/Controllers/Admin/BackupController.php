<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\DatabaseBackupService;
use App\Services\DriveBackupRunner;
use App\Services\GoogleDriveBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $backups, GoogleDriveBackupService $drive)
    {
        $this->authorizeBackup();

        return view('backEnd.backups.index', [
            'database' => $backups->databaseName(),
            'dumpAvailable' => $backups->dumpAvailable(),
            'lastSuccess' => BackupLog::where('type', 'database')->where('destination', 'download')->where('status', 'success')->latest()->first(),
            'lastDriveSuccess' => BackupLog::where('destination', 'google_drive')->where('status', 'success')->latest()->first(),
            'lastDriveFailure' => BackupLog::where('destination', 'google_drive')->where('status', 'failed')->latest()->first(),
            'driveEnabled' => (bool) config('backups.drive.enabled'),
            'driveConfigured' => $drive->configured(),
            'logs' => BackupLog::where('type', 'database')->with('creator:id,name')->latest()->limit(20)->get(),
        ]);
    }

    public function backupNow(DriveBackupRunner $runner): RedirectResponse
    {
        $this->authorizeBackup();

        try {
            $result = $runner->run(Auth::guard('admin')->id());
            return back()->with('success', $result['warning']
                ? 'Backup uploaded successfully, but '.$result['warning']
                : 'Backup completed and uploaded to Google Drive.');
        } catch (Throwable) {
            return back()->with('error', 'Backup failed. Check backup logs.');
        }
    }

    public function download(DatabaseBackupService $backups): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeBackup();

        $lock = Cache::lock('admin-database-backup-generation', 650);
        if (! $lock->get()) {
            return back()->with('error', 'A database backup is already being generated. Please try again shortly.');
        }

        $generated = null;
        try {
            $generated = $backups->generate();
            BackupLog::create([
                'type' => 'database',
                'filename' => $generated['filename'],
                'size_bytes' => $generated['size'],
                'status' => 'success',
                'created_by' => Auth::guard('admin')->id(),
            ]);

            return response()->download($generated['path'], $generated['filename'], [
                'Content-Type' => 'application/gzip',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            if ($generated !== null) @unlink($generated['path']);
            Log::error('Database backup generation failed', ['exception' => get_class($e), 'reason' => $e->getMessage()]);
            try {
                BackupLog::create([
                    'type' => 'database',
                    'filename' => 'bilaighor-db-'.now()->format('Y-m-d-H-i-s').'.sql.gz',
                    'status' => 'failed',
                    'created_by' => Auth::guard('admin')->id(),
                    'error_message' => 'Backup generation failed',
                ]);
            } catch (Throwable $auditError) {
                Log::error('Database backup audit failed', ['exception' => get_class($auditError)]);
            }

            return back()->with('error', 'Database backup could not be generated. Please check server backup configuration.');
        } finally {
            $lock->release();
        }
    }

    private function authorizeBackup(): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user && (
            (int) $user->id === 1 || $user->hasRole('Admin') || $user->hasRole('admin')
        ) && $user->can('database-backup-download'), 403);
    }
}
