<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_purpose', 32)->default('sale')->index();
        });

        Schema::create('order_purpose_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->index();
            $table->string('old_purpose', 32);
            $table->string('new_purpose', 32);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_purpose_changes');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_purpose']);
            $table->dropColumn('order_purpose');
        });
    }
};
