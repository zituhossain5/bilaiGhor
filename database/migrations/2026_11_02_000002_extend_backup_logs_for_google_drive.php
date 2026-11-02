<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->string('destination', 32)->default('download');
            $table->string('drive_file_id', 191)->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('warning_message', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('backup_logs', function (Blueprint $table) {
            $table->dropColumn(['destination', 'drive_file_id', 'encrypted', 'started_at', 'completed_at', 'warning_message']);
        });
    }
};
