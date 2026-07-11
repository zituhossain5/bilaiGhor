<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer saved addresses for the account "Addresses" page and
     * checkout "Select Address" modal. is_default marks the single
     * default address per customer.
     */
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->index();
            $table->string('label', 55)->nullable();
            $table->string('name', 155);
            $table->string('phone', 55);
            $table->string('email', 155)->nullable();
            $table->text('address');
            $table->unsignedInteger('division_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->unsignedInteger('upazila_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
