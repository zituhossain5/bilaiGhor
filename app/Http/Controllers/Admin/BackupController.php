<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Models\GoogleDriveBackupConnection;
use App\Services\DatabaseBackupService;
use App\Services\DriveBackupRunner;
use App\Services\GoogleDriveBackupService;
use App\Services\GoogleDriveOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $backups, GoogleDriveBackupService $drive, GoogleDriveOAuthService $oauth)
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
            'oauthConfigured' => $oauth->configured(),
            'driveConnection' => GoogleDriveBackupConnection::find(1),
            'logs' => BackupLog::where('type', 'database')->with('creator:id,name')->latest()->limit(20)->get(),
        ]);
    }

    public function connect(Request $request, GoogleDriveOAuthService $oauth): RedirectResponse
    {
        $this->authorizeBackup();
        if (! $oauth->configured()) return back()->with('error', 'Configure Google OAuth client settings first.');
        if (GoogleDriveBackupConnection::find(1)?->isConnected()) {
            return back()->with('success', 'Google Drive is already connected.');
        }

        $state = Str::random(64);
        $request->session()->put('backup_google_oauth', [
            'state' => $state, 'admin_id' => Auth::guard('admin')->id(),
        ]);
        return redirect()->away($oauth->authorizationUrl($state, true));
    }

    public function callback(Request $request, GoogleDriveOAuthService $oauth, GoogleDriveBackupService $drive): RedirectResponse
    {
        $this->authorizeBackup();
        $pending = $request->session()->pull('backup_google_oauth');
        $state = $request->query('state');
        abort_unless(is_array($pending) && is_string($state)
            && is_string($pending['state'] ?? null)
            && hash_equals($pending['state'], $state)
            && (int) ($pending['admin_id'] ?? 0) === (int) Auth::guard('admin')->id(), 403);

        if ($request->has('error')) return redirect()->route('admin.backups.index')->with('error', 'Google authorization was not completed.');
        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 4096) {
            return redirect()->route('admin.backups.index')->with('error', 'Google did not provide an authorization code.');
        }

        try {
            $tokens = $oauth->exchangeCode($code);
            $destination = $drive->prepareConnection($tokens['access_token']);
            $oauth->saveConnection($tokens, $destination['folder_id'], $destination['email']);
            return redirect()->route('admin.backups.index')->with('success', 'Google Drive connected. Your private My Drive backup folder is ready.');
        } catch (Throwable $e) {
            $safe = [
                'Google did not return offline Drive access. Reconnect and approve access.',
                'Backup folder is unavailable to this app. Clear the configured folder ID or disconnect and reconnect to create a new folder.',
                'Configured Google Drive ID is not an active folder.',
                'Google Drive cannot add files to the backup folder.',
                'Google Drive API is not enabled for this project.',
                'Google Drive storage quota or item limit exceeded.',
            ];
            return redirect()->route('admin.backups.index')->with('error',
                in_array($e->getMessage(), $safe, true) ? $e->getMessage() : 'Google Drive connection failed. Check the OAuth and folder setup.');
        }
    }

    public function testConnection(GoogleDriveBackupService $drive): RedirectResponse
    {
        $this->authorizeBackup();
        try {
            $drive->checkAccess(true);
            return back()->with('success', 'Google Drive connection successful.');
        } catch (Throwable $e) {
            return back()->with('error', 'Google Drive connection test failed. Check the folder and reconnect if needed.');
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->authorizeBackup();
        $request->session()->forget('backup_google_oauth');
        GoogleDriveBackupConnection::query()->whereKey(1)->delete();
        return back()->with('success', 'Google Drive disconnected. Existing Drive backups and history were kept.');
    }

    public function backupNow(DriveBackupRunner $runner): RedirectResponse
    {
        $this->authorizeBackup();

        if (! app(GoogleDriveBackupService::class)->configured()) {
            return back()->with('error', 'Connect Google Drive before creating an off-server backup.');
        }

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
