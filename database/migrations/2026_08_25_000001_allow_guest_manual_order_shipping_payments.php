<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `shippings` MODIFY `customer_id` INT NULL');
        DB::statement('ALTER TABLE `payments` MODIFY `customer_id` INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `shippings` MODIFY `customer_id` INT NOT NULL');
        DB::statement('ALTER TABLE `payments` MODIFY `customer_id` INT NOT NULL');
    }
};
