@extends('backEnd.layouts.master')
@section('title', 'Backup Management')

@section('content')
<div class="container-fluid py-3" style="max-width:1100px;color:var(--bilai-text,#3a210f)">
    <h3 class="mb-1">Backup Management</h3>
    <p class="text-muted mb-4">Create and download a fresh database backup.</p>

    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

    <div class="card mb-4" style="background:var(--bilai-surface,#fffdf8);border:1px solid var(--bilai-border,#ead8be)">
        <div class="card-body">
            <h4 class="mb-3">Database Backup</h4>
            <p>Includes products, orders, customers, inventory, expenses, accounting records, categories, SEO data and application settings stored in the database.</p>
            <p class="text-muted">Does not include uploaded images, application files, source code or the <code>.env</code> file.</p>
            <dl class="row mb-3">
                <dt class="col-sm-3">Database</dt><dd class="col-sm-9">{{ $database }}</dd>
                <dt class="col-sm-3">Database engine</dt><dd class="col-sm-9">MySQL / MariaDB</dd>
                <dt class="col-sm-3">Last generated</dt><dd class="col-sm-9">{{ $lastSuccess?->created_at?->format('d M Y, h:i A') ?? 'Never' }}</dd>
                <dt class="col-sm-3">mysqldump status</dt><dd class="col-sm-9">{{ $dumpAvailable ? 'Available' : 'Unavailable' }}</dd>
            </dl>
            <form method="POST" action="{{ route('admin.backups.database') }}" id="database-backup-form">
                @csrf
                <button type="submit" class="btn btn-primary" id="database-backup-button" @disabled(!$dumpAvailable)>Download Database Backup</button>
            </form>
            <p class="small text-muted mt-3 mb-0">This backup contains database data only. Uploaded images and application files are not included. Keep your cPanel/provider backups as an additional backup layer. The download may contain sensitive customer data and stored settings; keep it secure.</p>
        </div>
    </div>

    <div class="card mb-4" style="background:var(--bilai-surface,#fffdf8);border:1px solid var(--bilai-border,#ead8be)">
        <div class="card-body">
            <h4 class="mb-3">Google Drive Backup</h4>
            <dl class="row mb-3">
                <dt class="col-sm-4">Connection</dt><dd class="col-sm-8">{{ $driveConnection?->isConnected() ? 'Connected' : ($driveConnection?->requires_reauth ? 'Reconnect required' : 'Not connected') }}</dd>
                <dt class="col-sm-4">Google account</dt><dd class="col-sm-8">{{ $driveConnection?->google_email ?? 'Not connected' }}</dd>
                <dt class="col-sm-4">Destination</dt><dd class="col-sm-8">My Drive / Bilai Ghor Backups</dd>
                <dt class="col-sm-4">Automatic backup</dt><dd class="col-sm-8">{{ $driveEnabled ? 'Enabled' : 'Disabled' }}</dd>
                <dt class="col-sm-4">Schedule</dt><dd class="col-sm-8">Daily at {{ config('backups.daily_time') }} ({{ config('backups.timezone') }})</dd>
                <dt class="col-sm-4">Retention</dt><dd class="col-sm-8">{{ config('backups.retention_days') }} days</dd>
                <dt class="col-sm-4">Encryption</dt><dd class="col-sm-8">{{ config('backups.encryption.enabled') ? 'Enabled' : 'Disabled' }}</dd>
                <dt class="col-sm-4">Drive status</dt><dd class="col-sm-8">{{ $driveConfigured ? 'Connected; test folder access' : ($oauthConfigured ? 'Connect Google Drive' : 'Configure OAuth client') }}</dd>
                <dt class="col-sm-4">Backup folder</dt><dd class="col-sm-8">{{ $driveConnection?->folder_id ? 'Configured' : 'Will be created during connection' }}</dd>
                <dt class="col-sm-4">Last successful upload</dt><dd class="col-sm-8">{{ $lastDriveSuccess?->completed_at?->format('d M Y, h:i A') ?? 'Never' }}</dd>
                <dt class="col-sm-4">Last failed attempt</dt><dd class="col-sm-8">{{ $lastDriveFailure?->completed_at?->format('d M Y, h:i A') ?? 'None' }}</dd>
                <dt class="col-sm-4">Last uploaded file</dt><dd class="col-sm-8">{{ $lastDriveSuccess?->filename ?? '—' }}</dd>
                <dt class="col-sm-4">Backup size</dt><dd class="col-sm-8">{{ $lastDriveSuccess?->size_bytes !== null ? number_format($lastDriveSuccess->size_bytes / 1024 / 1024, 2).' MB' : '—' }}</dd>
            </dl>
            @if($driveConnection?->requires_reauth)<div class="alert alert-warning">Google Drive authorization expired. Reconnect Google Drive before the next backup.</div>@endif
            @if(!$driveConnection?->isConnected() && config('backups.drive.folder_id'))<div class="alert alert-info">A folder made manually in My Drive is not accessible with the narrow Drive permission by ID alone. Clear GOOGLE_DRIVE_BACKUP_FOLDER_ID and connect; Bilai Ghor will create its own private backup folder in My Drive.</div>@endif
            @if($lastDriveSuccess?->warning_message)<div class="alert alert-warning">{{ $lastDriveSuccess->warning_message }}</div>@endif
            <div class="d-flex flex-wrap gap-2 mb-3">
                @if(!$driveConnection?->isConnected())
                    <a href="{{ route('admin.backups.google.connect') }}" class="btn btn-primary {{ !$oauthConfigured ? 'disabled' : '' }}" @if(!$oauthConfigured) aria-disabled="true" @endif>Connect Google Drive</a>
                @else
                    <form method="POST" action="{{ route('admin.backups.google.test') }}">@csrf<button type="submit" class="btn btn-outline-primary">Test Google Drive Connection</button></form>
                    <form method="POST" action="{{ route('admin.backups.google.disconnect') }}" onsubmit="return confirm('Disconnect Google Drive? Existing backup files and history will be kept.');">@csrf<button type="submit" class="btn btn-outline-secondary">Disconnect Google Drive</button></form>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.backups.google-drive') }}" id="drive-backup-form">
                @csrf
                <button type="submit" class="btn btn-primary" id="drive-backup-button" @disabled(!$driveEnabled || !$driveConfigured || !$dumpAvailable)>Backup Now to Google Drive</button>
            </form>
            <p class="small text-muted mt-3 mb-0">Google Drive backups are an off-server backup layer. Continue keeping cPanel/provider backups as an additional safety layer. No public Drive links are created.</p>
        </div>
    </div>

    <div class="card" style="background:var(--bilai-surface,#fffdf8);border:1px solid var(--bilai-border,#ead8be)">
        <div class="card-body">
            <h4 class="mb-3">Recent backup attempts</h4>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Date</th><th>Filename</th><th>Destination</th><th>Size</th><th>Encrypted</th><th>Status</th><th>Triggered by</th></tr></thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr><td>{{ $log->created_at?->format('d M Y, h:i A') }}</td><td>{{ $log->filename }}</td><td>{{ $log->destination === 'google_drive' ? 'Google Drive' : 'Download' }}</td><td>{{ $log->size_bytes !== null ? number_format($log->size_bytes / 1024, 1).' KB' : '—' }}</td><td>{{ $log->encrypted ? 'Yes' : 'No' }}</td><td>{{ ucfirst($log->status) }}@if($log->warning_message)<span class="text-warning"> (warning)</span>@endif</td><td>{{ $log->created_by ? ($log->creator?->name ?? 'Admin') : 'Scheduler' }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No backup attempts yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('database-backup-form').addEventListener('submit', function () {
    const button = document.getElementById('database-backup-button');
    button.disabled = true;
    button.textContent = 'Generating backup...';
    // A downloaded response leaves the page open; allow another request later.
    setTimeout(function () { button.disabled = false; button.textContent = 'Download Database Backup'; }, 30000);
});
document.getElementById('drive-backup-form').addEventListener('submit', function () {
    const button = document.getElementById('drive-backup-button');
    button.disabled = true;
    button.textContent = 'Generating and uploading backup...';
});
</script>
@endsection
