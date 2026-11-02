@extends('backEnd.layouts.master')
@section('title', 'Backup Management')

@section('content')
<div class="container-fluid py-3" style="max-width:1100px;color:var(--bilai-text,#3a210f)">
    <h3 class="mb-1">Backup Management</h3>
    <p class="text-muted mb-4">Create and download a fresh database backup.</p>

    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

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

    <div class="card" style="background:var(--bilai-surface,#fffdf8);border:1px solid var(--bilai-border,#ead8be)">
        <div class="card-body">
            <h4 class="mb-3">Recent backup attempts</h4>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Date</th><th>Filename</th><th>Size</th><th>Status</th><th>Created by</th></tr></thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr><td>{{ $log->created_at?->format('d M Y, h:i A') }}</td><td>{{ $log->filename }}</td><td>{{ $log->size_bytes !== null ? number_format($log->size_bytes / 1024, 1).' KB' : '—' }}</td><td>{{ ucfirst($log->status) }}</td><td>{{ $log->created_by ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No backup attempts yet.</td></tr>
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
</script>
@endsection
