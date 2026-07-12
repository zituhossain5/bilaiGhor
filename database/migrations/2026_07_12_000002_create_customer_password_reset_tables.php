<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer password reset storage.
     *
     * 1) customer_password_resets — token table for the "customers" password broker.
     *    It previously pointed at `password_resets`, which the "admins" broker also
     *    uses; sharing one table means an admin and a customer with the same email
     *    overwrite each other's token. Own table = no cross-guard collision.
     *
     * 2) password_reset_otps — SMS OTP flow. OTP is stored HASHED, expires, counts
     *    attempts, and is single-use.
     */
    public function up(): void
    {
        if (!Schema::hasTable('customer_password_resets')) {
            Schema::create('customer_password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('password_reset_otps')) {
            Schema::create('password_reset_otps', function (Blueprint $table) {
                $table->id();
                // Which account the OTP belongs to. user_type keeps the existing
                // customer/vendor/reseller phone-reset flows working.
                $table->string('user_type', 20)->default('customer');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('mobile', 20)->index();
                $table->string('otp_hash');
                $table->timestamp('expires_at');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->string('request_ip', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
        Schema::dropIfExists('customer_password_resets');
    }
};
