<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\User;
use App\Services\BackupEncryptionService;
use App\Services\DatabaseBackupService;
use App\Services\DriveBackupRunner;
use App\Services\GoogleDriveBackupService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    public function test_drive_check_rejects_personal_my_drive_without_creating_a_backup(): void
    {
        $service = new GoogleDriveBackupService;
        (new \ReflectionProperty($service, 'accessToken'))->setValue($service, 'test-token');
        Http::fake(['https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([
            'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
            'trashed' => false, 'capabilities' => ['canAddChildren' => true],
        ], 200)]);

        try {
            $service->checkAccess();
            $this->fail('Personal My Drive folder was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Backup folder is in My Drive; use a Google Workspace Shared Drive.', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_drive_check_accepts_writable_shared_drive_folder(): void
    {
        $service = new GoogleDriveBackupService;
        (new \ReflectionProperty($service, 'accessToken'))->setValue($service, 'test-token');
        Http::fake(['https://www.googleapis.com/drive/v3/files/test-folder*' => Http::response([
            'id' => 'test-folder', 'mimeType' => 'application/vnd.google-apps.folder',
            'driveId' => 'shared-drive-id', 'trashed' => false,
            'capabilities' => ['canAddChildren' => true],
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
            'driveId' => 'shared-drive-id', 'trashed' => false,
            'capabilities' => ['canAddChildren' => false],
        ], 200)]);

        $this->expectExceptionMessage('Service account cannot add files to the backup folder.');
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
        $credentials = $this->fakeCredentials();
        config()->set('backups.drive.credentials', $credentials);
        $metadata = [
            'id' => 'uploaded-file', 'name' => $backup['filename'], 'size' => (string) $backup['size'],
            'createdTime' => now()->toIso8601String(), 'parents' => ['test-folder'], 'trashed' => false,
        ];
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token'], 200),
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
            Http::assertSentCount(4);
        } finally { @unlink($backup['path']); @unlink($credentials); }
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
        $runner = Mockery::mock(DriveBackupRunner::class);
        $runner->shouldReceive('run')->once()->andThrow(new \RuntimeException('private-key-marker'));
        $this->app->instance(DriveBackupRunner::class, $runner);

        $response = $this->actingAs($admin, 'admin')->from(route('admin.backups.index'))
            ->post(route('admin.backups.google-drive'))
            ->assertSessionHas('error', 'Backup failed. Check backup logs.');
        $this->assertStringNotContainsString('private-key-marker', $response->getContent());
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

    private function fakeCredentials(): string
    {
        $opensslConfig = tempnam(sys_get_temp_dir(), 'openssl-test-');
        file_put_contents($opensslConfig, "[req]\ndistinguished_name=req_dn\n[req_dn]\n");
        try {
            $private = openssl_pkey_new([
                'private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048,
                'config' => $opensslConfig,
            ]);
            $this->assertNotFalse($private);
            $this->assertTrue(openssl_pkey_export($private, $pem, null, ['config' => $opensslConfig]));
        } finally { @unlink($opensslConfig); }

        $path = tempnam(sys_get_temp_dir(), 'drive-test-');
        file_put_contents($path, json_encode([
            'type' => 'service_account', 'client_email' => 'test@example.test', 'private_key' => $pem,
        ]));
        return $path;
    }

}
