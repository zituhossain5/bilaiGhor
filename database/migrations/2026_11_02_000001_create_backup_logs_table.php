<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('filename', 100);
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('status', 16);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('error_message', 255)->nullable();
            $table->timestamps();
        });

        Permission::firstOrCreate(['name' => 'database-backup-download', 'guard_name' => 'admin']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Permission::where('name', 'database-backup-download')->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
