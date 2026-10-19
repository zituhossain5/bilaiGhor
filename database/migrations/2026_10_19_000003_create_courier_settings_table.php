<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Key/value switches for courier sending (App\Support\CourierSettings holds the keys and defaults). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_settings');
    }
};
