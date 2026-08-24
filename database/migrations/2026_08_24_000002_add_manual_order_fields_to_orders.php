<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'is_manual_order')) {
                $table->boolean('is_manual_order')->default(false)->after('id')->index();
            }
            if (!Schema::hasColumn('orders', 'invoice_number')) {
                $table->string('invoice_number', 55)->nullable()->unique()->after('invoice_id');
            }
            if (!Schema::hasColumn('orders', 'order_source')) {
                $table->string('order_source', 50)->nullable()->after('order_status')->index();
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 55)->nullable()->after('order_source')->index();
            }
            if (!Schema::hasColumn('orders', 'note')) {
                $table->text('note')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'order_note')) {
                $table->text('order_note')->nullable()->after('note');
            }
            if (!Schema::hasColumn('orders', 'manual_customer_name')) {
                $table->string('manual_customer_name', 155)->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('orders', 'manual_customer_phone')) {
                $table->string('manual_customer_phone', 55)->nullable()->after('manual_customer_name');
            }
            if (!Schema::hasColumn('orders', 'manual_customer_email')) {
                $table->string('manual_customer_email', 155)->nullable()->after('manual_customer_phone');
            }
            if (!Schema::hasColumn('orders', 'manual_customer_address')) {
                $table->text('manual_customer_address')->nullable()->after('manual_customer_email');
            }
            if (!Schema::hasColumn('orders', 'order_discount')) {
                $table->decimal('order_discount', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('orders', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('orders', 'due_amount')) {
                $table->decimal('due_amount', 12, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 55)->nullable()->after('due_amount');
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id', 100)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('order_source')->index();
            }
            if (!Schema::hasColumn('orders', 'public_token')) {
                $table->string('public_token', 80)->nullable()->unique()->after('created_by');
            }
        });

        DB::statement('ALTER TABLE `orders` MODIFY `customer_id` INT NULL');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'is_manual_order',
                'invoice_number',
                'order_source',
                'manual_customer_name',
                'manual_customer_phone',
                'manual_customer_email',
                'manual_customer_address',
                'order_discount',
                'paid_amount',
                'due_amount',
                'payment_method',
                'transaction_id',
                'created_by',
                'public_token',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
