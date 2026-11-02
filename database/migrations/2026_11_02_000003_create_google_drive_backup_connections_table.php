<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('google_drive_backup_connections', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('google_email')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('scope')->nullable();
            $table->string('folder_id', 191)->nullable();
            $table->boolean('requires_reauth')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_backup_connections');
    }
};
