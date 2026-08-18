<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('ticket_number', 32)->nullable()->unique()->after('id');
            $table->string('email')->nullable()->after('phone');
            $table->string('order_reference', 55)->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropUnique(['ticket_number']);
            $table->dropColumn(['ticket_number', 'email', 'order_reference']);
        });
    }
};
