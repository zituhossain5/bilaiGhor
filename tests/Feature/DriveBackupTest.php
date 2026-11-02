<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\GoogleDriveBackupConnection;
use App\Models\User;
use App\Services\BackupEncryptionService;
use App\Services\DatabaseBackupService;
use App\Services\DriveBackupRunner;
use App\Services\GoogleDriveBackupService;
use App\Services\GoogleDriveOAuthService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriveBackupTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config()->set('backups.drive.enabled', true);
        config()->set('backups.drive.folder_id', 'test-folder');
        config()->set('backups.drive.client_id', 'test-client-id');
        config()->set('backups.drive.client_secret', 'test-client-secret');
        config()->set('backups.drive.redirect_uri', 'https://bilaighor.bd/admin/backups/google/callback');
        config()->set('backups.encryption.enabled', false);
        config()->set('backups.retention_days', 14);
    }

    public function test_scheduler_uploads_verifies_logs_and_removes_local_file(): void
    {
        $backup = $this->fakeBackup();
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldReceive('generate')->once()->with(true)->andReturn($backup);
        $this->app->instance(DatabaseBackupService::class, $dump);
        $drive = Mockery::mock(GoogleDriveBackupService::class);
        $drive->shouldReceive('configured')->once()->andReturn(true);
        $drive->shouldReceive('checkAccess')->once();
        $drive->shouldReceive('uploadAndVerify')->once()->with($backup['path'], $backup['filename'], $backup['size'])
            ->andReturn(['id' => 'drive-file-123', 'size' => $backup['size'], 'created_at' => now()->toIso8601String()]);
        $drive->shouldReceive('pruneOldBackups')->once()->with(14, 'drive-file-123')->andReturn(2);
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        try {
            $this->assertSame(0, $this->artisan('backup:database-to-drive')->run());
            $this->assertDatabaseHas('backup_logs', [
                'destination' => 'google_drive', 'drive_file_id' => 'drive-file-123',
                'status' => 'success', 'created_by' => null, 'encrypted' => false,
            ]);
            $this->assertFileDoesNotExist($backup['path']);
        } finally { @unlink($backup['path']); }
    }

    public function test_failed_dump_never_uploads(): void
    {
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldReceive('generate')->once()->with(true)->andThrow(new \RuntimeException('dump secret'));
        $this->app->instance(DatabaseBackupService::class, $dump);
        $drive = Mockery::mock(GoogleDriveBackupService::class);
        $drive->shouldReceive('configured')->once()->andReturn(true);
        $drive->shouldReceive('checkAccess')->once();
        $drive->shouldNotReceive('uploadAndVerify');
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        $this->assertSame(1, $this->artisan('backup:database-to-drive')->run());
        $this->assertDatabaseHas('backup_logs', [
            'destination' => 'google_drive', 'status' => 'failed', 'error_message' => 'Database dump failed.',
        ]);
    }

    public function test_missing_drive_configuration_stops_before_dump(): void
    {
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldNotReceive('generate');
        $this->app->instance(DatabaseBackupService::class, $dump);
        $drive = Mockery::mock(GoogleDriveBackupService::class);
        $drive->shouldReceive('configured')->once()->andReturn(false);
        $drive->shouldNotReceive('uploadAndVerify');
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        $this->assertSame(1, $this->artisan('backup:database-to-drive')->run());
        $this->assertDatabaseHas('backup_logs', [
            'destination' => 'google_drive', 'status' => 'failed',
            'error_message' => 'Google Drive backup is not configured or accessible.',
        ]);
    }

    public function test_invalid_encryption_key_stops_before_dump(): void
    {
        config()->set('backups.encryption.enabled', true);
        config()->set('backups.encryption.key', 'invalid-key');
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldNotReceive('generate');
        $this->app->instance(DatabaseBackupService::class, $dump);
        $this->assertSame(1, $this->artisan('backup:database-to-drive')->run());
        $this->assertDatabaseHas('backup_logs', [
            'destination' => 'google_drive', 'status' => 'failed',
            'error_message' => 'Backup encryption failed.',
        ]);
    }

    public function test_failed_upload_keeps_private_backup_and_does_not_claim_success(): void
    {
        $backup = $this->fakeBackup();
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldReceive('generate')->once()->with(true)->andReturn($backup);
        $this->app->instance(DatabaseBackupService::class, $dump);
        $drive = Mockery::mock(GoogleDriveBackupService::class);
        $drive->shouldReceive('configured')->once()->andReturn(true);
        $drive->shouldReceive('checkAccess')->once();
        $drive->shouldReceive('uploadAndVerify')->once()->andThrow(new \RuntimeException('secret remote response'));
        $drive->shouldNotReceive('pruneOldBackups');
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        try {
            $this->assertSame(1, $this->artisan('backup:database-to-drive')->run());
            $this->assertFileExists($backup['path']);
            $this->assertDatabaseHas('backup_logs', ['status' => 'failed', 'destination' => 'google_drive']);
            $this->assertDatabaseMissing('backup_logs', ['status' => 'success', 'destination' => 'google_drive']);
        } finally { @unlink($backup['path']); }
    }

    public function test_duplicate_run_is_blocked(): void
    {
        $lock = Cache::lock('admin-database-backup-generation', 30);
        $this->assertTrue($lock->get());
        try {
            $this->expectExceptionMessage('already running');
            app(DriveBackupRunner::class)->run();
        } finally { $lock->release(); }
    }

    public function test_stale_private_failed_backups_are_cleaned_without_touching_unrelated_files(): void
    {
        $directory = storage_path('app/private/backups/tmp');
        if (! is_dir($directory)) mkdir($directory, 0700, true);
        $old = $directory.'/'.Str::random(20).'-bilaighor-db-2026-10-01-02-00-00.sql.gz';
        $fresh = $directory.'/'.Str::random(20).'-bilaighor-db-2026-11-02-02-00-00.sql.gz';
        $unrelated = $directory.'/unrelated-'.Str::random(12).'.txt';
        foreach ([$old, $fresh, $unrelated] as $file) file_put_contents($file, 'test');
        touch($old, time() - 3 * 86400);
        touch($unrelated, time() - 3 * 86400);
        try {
            $this->assertSame(1, app(DatabaseBackupService::class)->pruneStaleDriveFiles());
            $this->assertFileDoesNotExist($old);
            $this->assertFileExists($fresh);
            $this->assertFileExists($unrelated);
        } finally { foreach ([$old, $fresh, $unrelated] as $file) @unlink($file); }
    }

    public function test_retention_failure_is_warning_on_success(): void
    {
        $backup = $this->fakeBackup();
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldReceive('generate')->once()->with(true)->andReturn($backup);
        $this->app->instance(DatabaseBackupService::class, $dump);
        $drive = Mockery::mock(GoogleDriveBackupService::class);
        $drive->shouldReceive('configured')->once()->andReturn(true);
        $drive->shouldReceive('checkAccess')->once();
        $drive->shouldReceive('uploadAndVerify')->once()->andReturn([
            'id' => 'drive-file-456', 'size' => $backup['size'], 'created_at' => now()->toIso8601String(),
        ]);
        $drive->shouldReceive('pruneOldBackups')->once()->andThrow(new \RuntimeException('private API details'));
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        try {
            $result = app(DriveBackupRunner::class)->run(42);
            $this->assertSame('success', $result['log']->status);
            $this->assertSame(42, $result['log']->created_by);
            $this->assertSame('Retention cleanup failed.', $result['warning']);
            $this->assertFileDoesNotExist($backup['path']);
        } finally { @unlink($backup['path']); }
    }

    public function test_sodium_encryption_produces_authenticated_ciphertext(): void
    {
        $backup = $this->fakeBackup();
        $key = random_bytes(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
        config()->set('backups.encryption.key', 'base64:'.base64_encode($key));
        $encrypted = null;
        try {
            $encrypted = app(BackupEncryptionService::class)->encrypt($backup);
            $this->assertStringEndsWith('.sql.gz.enc', $encrypted['filename']);
            $stream = fopen($encrypted['path'], 'rb');
            $this->assertSame("BILAI-GHOR-DB-BACKUP-1\n", fread($stream, 23));
            $header = fread($stream, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
            $plain = '';
            do {
                $length = unpack('N', fread($stream, 4))[1];
                [$chunk, $tag] = sodium_crypto_secretstream_xchacha20poly1305_pull($state, fread($stream, $length));
                $plain .= $chunk;
            } while ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);
            fclose($stream);
            $this->assertSame(file_get_contents($backup['path']), $plain);
        } finally {
            @unlink($backup['path']);
            if ($encrypted) @unlink($encrypted['path']);
        }
    }

    public function test_encrypted_runner_uploads_only_ciphertext_and_cleans_both_files(): void
    {
        $backup = $this->fakeBackup();
        config()->set('backups.encryption.enabled', true);
        config()->set('backups.encryption.key', 'base64:'.base64_encode(random_bytes(32)));
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldReceive('generate')->once()->with(true)->andReturn($backup);
        $this->app->instance(DatabaseBackupService::class, $dump);
        $encryptedPath = null;
        $drive = Mockery::mock(GoogleDriveBackupService::class);
        $drive->shouldReceive('configured')->once()->andReturn(true);
        $drive->shouldReceive('checkAccess')->once();
        $drive->shouldReceive('uploadAndVerify')->once()->andReturnUsing(function ($path, $name, $size) use (&$encryptedPath) {
            $encryptedPath = $path;
            $this->assertStringEndsWith('.sql.gz.enc', $name);
            $this->assertFileDoesNotExist(substr($path, 0, -4));
            return ['id' => 'encrypted-file', 'size' => $size, 'created_at' => now()->toIso8601String()];
        });
        $drive->shouldReceive('pruneOldBackups')->once()->andReturn(0);
        $this->app->instance(GoogleDriveBackupService::class, $drive);

        try {
            $result = app(DriveBackupRunner::class)->run();
            $this->assertTrue($result['log']->encrypted);
            $this->assertFileDoesNotExist($backup['path']);
            $this->assertFileDoesNotExist($encryptedPath);
        } finally { @unlink($backup['path']); if ($encryptedPath) @unlink($encryptedPath); }
    }

    public function test_retention_only_deletes_old_marked_backup_in_the_configured_folder(): void
    {
        $service = new GoogleDriveBackupService;
        (new \ReflectionProperty($service, 'accessToken'))->setValue($service, 'test-token');
        $old = now()->subDays(20)->toIso8601String();
        $recent = now()->subDay()->toIso8601String();
        $make = fn ($id, $name, $date, $marker = true) => [
            'id' => $id, 'name' => $name, 'createdTime' => $date,
            'parents' => ['test-folder'], 'trashed' => false,
            'appProperties' => $marker ? ['bilaighor_backup' => 'database'] : [],
        ];
        $valid = $make('old-file', 'bilaighor-db-2026-10-01-02-00-00.sql.gz', $old);
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/old-file*' => function ($request) use ($valid) {
                return $request->method() === 'DELETE' ? Http::response([], 204) : Http::response($valid, 200);
            },
            'https://www.googleapis.com/drive/v3/files*' => Http::response(['files' => [
                $valid,
                $make('unrelated-file', 'notes.txt', $old),
                $make('unmarked-file', 'bilaighor-db-2026-10-01-02-00-00.sql.gz', $old, false),
                $make('recent-file', 'bilaighor-db-2026-11-02-02-00-00.sql.gz', $recent),
            ]], 200),
        ]);
        $this->assertSame(1, $service->pruneOldBackups(14));
        Http::assertSentCount(3); // list, re-check, delete
        Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_contains($request->url(), '/old-file'));
    }

    public function test_drive_check_accepts_personal_my_drive_folder(): void
    {
        $service = new GoogleDriveBackupService;
        (new \ReflectionProperty($service, 'accessToken'))->setValue($service, 'test-token');
        Http::fake(['https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([
            'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
            'trashed' => false, 'capabilities' => ['canAddChildren' => true],
        ], 200)]);

        $service->checkAccess();
        Http::assertSentCount(1);
    }

    public function test_drive_check_reports_missing_write_permission(): void
    {
        $service = new GoogleDriveBackupService;
        (new \ReflectionProperty($service, 'accessToken'))->setValue($service, 'test-token');
        Http::fake(['https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([
            'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
            'trashed' => false,
            'capabilities' => ['canAddChildren' => false],
        ], 200)]);

        $this->expectExceptionMessage('Google Drive cannot add files to the backup folder.');
        $service->checkAccess();
    }

    public function test_admin_only_backup_now_route_uses_shared_runner(): void
    {
        $this->post(route('admin.backups.google-drive'))->assertRedirect();
        $create = fn () => User::create([
            'name' => 'Backup Admin', 'email' => 'drive-backup-'.Str::uuid().'@example.test',
            'password' => bcrypt(Str::random(20)), 'status' => 1,
        ]);
        $admin = $create();
        if ($admin->id === 1) $admin = $create();
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        $this->connection();
        $runner = Mockery::mock(DriveBackupRunner::class);
        $runner->shouldReceive('run')->once()->with($admin->id)->andReturn(['log' => new BackupLog, 'warning' => null]);
        $this->app->instance(DriveBackupRunner::class, $runner);
        $this->actingAs($admin, 'admin')->from(route('admin.backups.index'))
            ->post(route('admin.backups.google-drive'))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('success', 'Backup completed and uploaded to Google Drive.');
    }

    public function test_drive_upload_requires_verified_metadata_before_reporting_success(): void
    {
        $backup = $this->fakeBackup();
        $this->connection(token: 'expired-token', expires: now()->subMinute());
        $metadata = [
            'id' => 'uploaded-file', 'name' => $backup['filename'], 'size' => (string) $backup['size'],
            'createdTime' => now()->toIso8601String(), 'parents' => ['test-folder'], 'trashed' => false,
        ];
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'refreshed-token', 'expires_in' => 3600], 200),
            'https://www.googleapis.com/upload/drive/v3/files*' => function ($request) use ($metadata) {
                return $request->method() === 'POST'
                    ? Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=fake'])
                    : Http::response($metadata, 200);
            },
            'https://www.googleapis.com/drive/v3/files/uploaded-file*' => Http::response($metadata, 200),
        ]);

        try {
            $result = app(GoogleDriveBackupService::class)->uploadAndVerify(
                $backup['path'], $backup['filename'], $backup['size']
            );
            $this->assertSame('uploaded-file', $result['id']);
            $this->assertSame($backup['size'], $result['size']);
            $this->assertSame('refreshed-token', GoogleDriveBackupConnection::find(1)->access_token);
            Http::assertSentCount(4);
        } finally { @unlink($backup['path']); }
    }

    public function test_drive_metadata_mismatch_rejects_upload_and_preserves_local_file(): void
    {
        $backup = $this->fakeBackup();
        $service = new GoogleDriveBackupService;
        (new \ReflectionProperty($service, 'accessToken'))->setValue($service, 'test-token');
        Http::fake([
            'https://www.googleapis.com/upload/drive/v3/files*' => function ($request) {
                return $request->method() === 'POST'
                    ? Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=fake'])
                    : Http::response(['id' => 'wrong-size-file'], 200);
            },
            'https://www.googleapis.com/drive/v3/files/wrong-size-file*' => Http::response([
                'id' => 'wrong-size-file', 'name' => $backup['filename'], 'size' => '1',
                'parents' => ['test-folder'], 'trashed' => false,
            ], 200),
        ]);
        try {
            $this->expectExceptionMessage('verification failed');
            $service->uploadAndVerify($backup['path'], $backup['filename'], $backup['size']);
        } finally {
            $this->assertFileExists($backup['path']);
            @unlink($backup['path']);
        }
    }

    public function test_drive_failure_response_does_not_expose_exception_details(): void
    {
        $create = fn () => User::create([
            'name' => 'Backup Admin', 'email' => 'drive-redaction-'.Str::uuid().'@example.test',
            'password' => bcrypt(Str::random(20)), 'status' => 1,
        ]);
        $admin = $create();
        if ($admin->id === 1) $admin = $create();
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        $this->connection();
        $runner = Mockery::mock(DriveBackupRunner::class);
        $runner->shouldReceive('run')->once()->andThrow(new \RuntimeException('private-key-marker'));
        $this->app->instance(DriveBackupRunner::class, $runner);

        $response = $this->actingAs($admin, 'admin')->from(route('admin.backups.index'))
            ->post(route('admin.backups.google-drive'))
            ->assertSessionHas('error', 'Backup failed. Check backup logs.');
        $this->assertStringNotContainsString('private-key-marker', $response->getContent());
    }

    public function test_only_authorized_admin_can_start_google_connection(): void
    {
        $this->get(route('admin.backups.google.connect'))->assertRedirect();
        $staff = User::create([
            'name' => 'Backup Staff', 'email' => 'backup-staff-'.Str::uuid().'@example.test',
            'password' => bcrypt(Str::random(20)), 'status' => 1,
        ]);
        $this->actingAs($staff, 'admin')->get(route('admin.backups.google.connect'))->assertForbidden();
    }

    public function test_connect_uses_offline_scoped_oauth_and_stores_state_in_session(): void
    {
        $admin = $this->backupAdmin();
        $response = $this->actingAs($admin, 'admin')->get(route('admin.backups.google.connect'));
        $response->assertRedirect();
        $url = $response->headers->get('Location');
        $this->assertSame('accounts.google.com', parse_url($url, PHP_URL_HOST));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(GoogleDriveOAuthService::SCOPE, $query['scope']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame('https://bilaighor.bd/admin/backups/google/callback', $query['redirect_uri']);
        $this->assertSame($query['state'], session('backup_google_oauth.state'));
        $this->assertSame($admin->id, session('backup_google_oauth.admin_id'));
        Http::assertNothingSent();
    }

    public function test_callback_rejects_invalid_oauth_state_before_token_exchange(): void
    {
        $admin = $this->backupAdmin();
        $this->actingAs($admin, 'admin')->withSession([
            'backup_google_oauth' => ['state' => 'expected', 'admin_id' => $admin->id],
        ])->get(route('admin.backups.google.callback', ['state' => 'wrong', 'code' => 'fake-code']))->assertForbidden();
        Http::assertNothingSent();
        $this->assertDatabaseMissing('google_drive_backup_connections', ['id' => 1]);
    }

    public function test_callback_creates_private_app_folder_and_encrypts_tokens_at_rest(): void
    {
        config()->set('backups.drive.folder_id', null);
        $admin = $this->backupAdmin();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-secret-marker', 'refresh_token' => 'refresh-secret-marker',
                'expires_in' => 3600, 'scope' => GoogleDriveOAuthService::SCOPE,
            ], 200),
            'https://www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'owner@example.test']], 200),
            'https://www.googleapis.com/drive/v3/files*' => function ($request) {
                return $request->method() === 'POST'
                    ? Http::response(['id' => 'app-folder'], 200)
                    : Http::response([
                        'id' => 'app-folder', 'mimeType' => 'application/vnd.google-apps.folder',
                        'trashed' => false, 'capabilities' => ['canAddChildren' => true],
                    ], 200);
            },
        ]);
        $response = $this->actingAs($admin, 'admin')->withSession([
            'backup_google_oauth' => ['state' => 'valid-state', 'admin_id' => $admin->id],
        ])->get(route('admin.backups.google.callback', ['state' => 'valid-state', 'code' => 'fake-code']))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('success');

        $connection = GoogleDriveBackupConnection::find(1);
        $this->assertSame('app-folder', $connection->folder_id);
        $this->assertSame('owner@example.test', $connection->google_email);
        $this->assertSame('refresh-secret-marker', $connection->refresh_token);
        $this->assertNotSame('refresh-secret-marker', DB::table('google_drive_backup_connections')->where('id', 1)->value('refresh_token'));
        $this->assertNotSame('access-secret-marker', DB::table('google_drive_backup_connections')->where('id', 1)->value('access_token'));
        $this->assertStringNotContainsString('refresh-secret-marker', $response->getContent());
        $this->assertStringNotContainsString('access-secret-marker', $response->getContent());
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/drive/v3/files')
            && $request['name'] === 'Bilai Ghor Backups');
    }

    public function test_callback_without_refresh_token_does_not_claim_connection(): void
    {
        $admin = $this->backupAdmin();
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'access-only', 'expires_in' => 3600,
            'scope' => GoogleDriveOAuthService::SCOPE,
        ], 200)]);
        $this->actingAs($admin, 'admin')->withSession([
            'backup_google_oauth' => ['state' => 'valid-state', 'admin_id' => $admin->id],
        ])->get(route('admin.backups.google.callback', ['state' => 'valid-state', 'code' => 'fake-code']))
            ->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('error', 'Google did not return offline Drive access. Reconnect and approve access.');
        $this->assertDatabaseMissing('google_drive_backup_connections', ['id' => 1]);
        Http::assertSentCount(1);
    }

    public function test_callback_respects_a_folder_id_when_the_app_can_access_it(): void
    {
        $admin = $this->backupAdmin();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'test-access', 'refresh_token' => 'test-refresh',
                'expires_in' => 3600, 'scope' => GoogleDriveOAuthService::SCOPE,
            ], 200),
            'https://www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'owner@example.test']], 200),
            'https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([
                'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
                'trashed' => false, 'capabilities' => ['canAddChildren' => true],
            ], 200),
        ]);
        $this->actingAs($admin, 'admin')->withSession([
            'backup_google_oauth' => ['state' => 'valid-state', 'admin_id' => $admin->id],
        ])->get(route('admin.backups.google.callback', ['state' => 'valid-state', 'code' => 'fake-code']))
            ->assertSessionHas('success');
        $this->assertSame('test-folder', GoogleDriveBackupConnection::find(1)->folder_id);
        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/drive/v3/files'));
    }

    public function test_manually_created_folder_without_app_access_is_rejected_safely(): void
    {
        Http::fake(['https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([], 404)]);
        try {
            app(GoogleDriveBackupService::class)->prepareConnection('short-lived-test-token');
            $this->fail('An inaccessible My Drive folder was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Backup folder is unavailable to this app. Clear the configured folder ID or disconnect and reconnect to create a new folder.', $e->getMessage());
        }
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_revoked_refresh_token_marks_connection_for_reauthorization(): void
    {
        $this->connection(token: 'expired-token', expires: now()->subMinute());
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);
        try {
            app(GoogleDriveOAuthService::class)->accessToken();
            $this->fail('A revoked refresh token was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Google Drive authorization expired. Reconnect Google Drive from Backup Management.', $e->getMessage());
        }
        $connection = GoogleDriveBackupConnection::find(1);
        $this->assertTrue($connection->requires_reauth);
        $this->assertNull($connection->refresh_token);
        $this->assertNull($connection->access_token);
    }

    public function test_scheduled_job_refreshes_stored_token_and_uploads_without_browser_session(): void
    {
        config()->set('backups.drive.folder_id', null);
        $this->connection(token: 'expired-token', expires: now()->subMinute());
        $backup = $this->fakeBackup();
        $dump = Mockery::mock(DatabaseBackupService::class);
        $dump->shouldReceive('generate')->once()->with(true)->andReturn($backup);
        $this->app->instance(DatabaseBackupService::class, $dump);
        $metadata = [
            'id' => 'uploaded-file', 'name' => $backup['filename'],
            'size' => (string) $backup['size'], 'parents' => ['test-folder'],
            'trashed' => false, 'createdTime' => now()->toIso8601String(),
        ];
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-token', 'refresh_token' => 'rotated-refresh-token', 'expires_in' => 3600,
            ], 200),
            'https://www.googleapis.com/upload/drive/v3/files*' => function ($request) use ($metadata) {
                return $request->method() === 'POST'
                    ? Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=fake'])
                    : Http::response($metadata, 200);
            },
            'https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([
                'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
                'trashed' => false, 'capabilities' => ['canAddChildren' => true],
            ], 200),
            'https://www.googleapis.com/drive/v3/files/uploaded-file*' => Http::response($metadata, 200),
            'https://www.googleapis.com/drive/v3/files*' => Http::response(['files' => []], 200),
        ]);
        try {
            $this->assertSame(0, $this->artisan('backup:database-to-drive')->run());
            $this->assertDatabaseHas('backup_logs', [
                'destination' => 'google_drive', 'drive_file_id' => 'uploaded-file',
                'status' => 'success', 'created_by' => null,
            ]);
            $this->assertSame('new-token', GoogleDriveBackupConnection::find(1)->access_token);
            $this->assertSame('rotated-refresh-token', GoogleDriveBackupConnection::find(1)->refresh_token);
            $this->assertFileDoesNotExist($backup['path']);
            Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
                && $request['grant_type'] === 'refresh_token');
        } finally { @unlink($backup['path']); }
    }

    public function test_connection_test_creates_and_removes_a_disposable_file(): void
    {
        $this->connection();
        $admin = $this->backupAdmin();
        Http::fake(['https://www.googleapis.com/drive/v3/files*' => function ($request) {
            return match ($request->method()) {
                'GET' => str_contains($request->url(), '/temporary-marker')
                    ? Http::response(['id' => 'temporary-marker', 'parents' => ['test-folder'], 'trashed' => false], 200)
                    : Http::response([
                        'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
                        'trashed' => false, 'capabilities' => ['canAddChildren' => true],
                    ], 200),
                'POST' => Http::response(['id' => 'temporary-marker'], 200),
                default => Http::response([], 204),
            };
        }]);
        $this->actingAs($admin, 'admin')->from(route('admin.backups.index'))
            ->post(route('admin.backups.google.test'))
            ->assertSessionHas('success', 'Google Drive connection successful.');
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/temporary-marker'));
    }

    public function test_disconnect_removes_tokens_but_keeps_history_and_remote_files(): void
    {
        $this->connection();
        $admin = $this->backupAdmin();
        $log = BackupLog::create([
            'type' => 'database', 'destination' => 'google_drive', 'filename' => 'old.sql.gz',
            'status' => 'success', 'drive_file_id' => 'old-drive-file',
        ]);
        $this->actingAs($admin, 'admin')->from(route('admin.backups.index'))
            ->post(route('admin.backups.google.disconnect'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('google_drive_backup_connections', ['id' => 1]);
        $this->assertDatabaseHas('backup_logs', ['id' => $log->id]);
        Http::assertNothingSent();
    }

    private function backupAdmin(): User
    {
        $create = fn () => User::create([
            'name' => 'Backup Admin', 'email' => 'backup-oauth-'.Str::uuid().'@example.test',
            'password' => bcrypt(Str::random(20)), 'status' => 1,
        ]);
        $admin = $create();
        if ($admin->id === 1) $admin = $create();
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        return $admin;
    }

    private function fakeBackup(): array
    {
        $directory = storage_path('app/private/backups/tmp');
        if (! is_dir($directory)) mkdir($directory, 0700, true);
        $filename = 'bilaighor-db-2026-11-02-02-00-00.sql.gz';
        $path = $directory.'/'.Str::random(20).'-'.$filename;
        file_put_contents($path, gzencode('CREATE TABLE test_backup (id INT);'));
        return ['path' => $path, 'filename' => $filename, 'size' => filesize($path)];
    }

    private function connection(string $token = 'test-token', mixed $expires = null): GoogleDriveBackupConnection
    {
        return GoogleDriveBackupConnection::create([
            'id' => 1, 'google_email' => 'owner@example.test',
            'access_token' => $token, 'refresh_token' => 'encrypted-refresh-marker',
            'token_expires_at' => $expires ?? now()->addHour(),
            'scope' => GoogleDriveOAuthService::SCOPE,
            'folder_id' => 'test-folder', 'connected_at' => now(),
        ]);
    }

}
