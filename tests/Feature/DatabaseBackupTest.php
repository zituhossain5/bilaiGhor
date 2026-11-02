<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_view_or_generate_database_backup(): void
    {
        $this->get(route('admin.backups.index'))->assertRedirect();
        $this->post(route('admin.backups.database'))->assertRedirect();
        $this->get(route('admin.backups.database'))->assertMethodNotAllowed();
    }

    public function test_staff_cannot_access_backup_even_with_the_permission(): void
    {
        $staff = $this->user();
        $staff->assignRole(Role::findOrCreate('backup-staff-'.Str::random(8), 'admin'));
        $staff->givePermissionTo(Permission::findOrCreate('database-backup-download', 'admin'));

        $this->actingAs($staff, 'admin')->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($staff, 'admin')->post(route('admin.backups.database'))->assertForbidden();
    }

    public function test_admin_sees_backup_page_and_post_download_is_private_audited_and_deleted(): void
    {
        $admin = $this->user();
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        $this->actingAs($admin, 'admin')->get(route('admin.backups.index'))->assertOk()
            ->assertSee('Backup Management')->assertSee('Download Database Backup');

        $directory = storage_path('app/private/backups');
        if (! is_dir($directory)) mkdir($directory, 0700, true);
        $path = $directory.'/test-'.Str::random(12).'.sql.gz';
        file_put_contents($path, gzencode('CREATE TABLE backup_test (id INT);'));
        $filename = 'bilaighor-db-2026-11-02-12-30-00.sql.gz';

        $mock = Mockery::mock(DatabaseBackupService::class);
        $mock->shouldReceive('generate')->once()->andReturn([
            'path' => $path, 'filename' => $filename, 'size' => filesize($path),
        ]);
        $this->app->instance(DatabaseBackupService::class, $mock);

        try {
            $response = $this->post(route('admin.backups.database'))->assertOk();
            $response->assertDownload($filename);
            $this->assertStringStartsWith(storage_path('app/private/backups'), $path);
            $this->assertDatabaseHas('backup_logs', [
                'filename' => $filename, 'status' => 'success', 'created_by' => $admin->id,
            ]);
            $this->get('/storage/backups/'.basename($path))->assertNotFound();
            $this->assertStringNotContainsString('DB_PASSWORD', (string) $response->headers->get('content-disposition'));

            ob_start();
            $response->baseResponse->sendContent();
            ob_end_clean();
            $this->assertFileDoesNotExist($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_failed_dump_creates_safe_audit_entry(): void
    {
        $admin = $this->user();
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        $mock = Mockery::mock(DatabaseBackupService::class);
        $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('secret-marker'));
        $this->app->instance(DatabaseBackupService::class, $mock);

        $this->actingAs($admin, 'admin')->from(route('admin.backups.index'))
            ->post(route('admin.backups.database'))->assertRedirect(route('admin.backups.index'))
            ->assertSessionHas('error', 'Database backup could not be generated. Please check server backup configuration.');
        $this->assertDatabaseHas('backup_logs', [
            'status' => 'failed', 'created_by' => $admin->id, 'error_message' => 'Backup generation failed',
        ]);
    }

    private function user(): User
    {
        $create = fn () => User::create([
            'name' => 'Backup Test', 'email' => 'backup-'.Str::uuid().'@example.test',
            'password' => bcrypt(Str::random(24)), 'status' => 1,
        ]);

        // ID 1 is the application's super admin; exercise other roles as a separate user.
        $user = $create();
        return $user->id === 1 ? $create() : $user;
    }
}
