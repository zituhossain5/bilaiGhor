<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('upazilas', 'thanas');

        Schema::table('thanas', function (Blueprint $table) {
            $table->string('name_bn', 190)->nullable()->after('name');
            $table->string('post_code', 20)->nullable()->after('name_bn');
            $table->decimal('delivery_charge', 10, 2)->default(0)->after('post_code');
        });

        DB::statement('UPDATE thanas t INNER JOIN districts d ON d.id = t.district_id SET t.delivery_charge = d.delivery_charge');

        $zoneToThana = [];
        foreach (DB::table('delivery_zones')->orderBy('id')->get() as $zone) {
            $thana = DB::table('thanas')
                ->where('district_id', $zone->district_id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($zone->name))])
                ->first();

            $attributes = [
                'name_bn' => $zone->name_bn,
                'post_code' => $zone->post_code,
                'delivery_charge' => (float) $zone->delivery_charge > 0
                    ? $zone->delivery_charge
                    : DB::table('districts')->where('id', $zone->district_id)->value('delivery_charge'),
                'status' => $zone->status,
                'sort_order' => $zone->sort_order,
                'updated_at' => $zone->updated_at ?? now(),
            ];

            if ($thana) {
                DB::table('thanas')->where('id', $thana->id)->update($attributes);
                $zoneToThana[$zone->id] = $thana->id;
                continue;
            }

            $zoneToThana[$zone->id] = DB::table('thanas')->insertGetId($attributes + [
                'district_id' => $zone->district_id,
                'name' => $zone->name,
                'created_at' => $zone->created_at ?? now(),
            ]);
        }

        foreach (['customers', 'customer_addresses', 'shippings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('thana_id')->nullable()->index();
            });
        }

        DB::table('customer_addresses')->whereNotNull('upazila_id')->update(['thana_id' => DB::raw('upazila_id')]);
        DB::table('shippings')->whereNotNull('upazila_id')->update(['thana_id' => DB::raw('upazila_id')]);

        foreach ($zoneToThana as $zoneId => $thanaId) {
            DB::table('customers')->where('zone_id', $zoneId)->update(['thana_id' => $thanaId]);
            DB::table('customer_addresses')->where('zone_id', $zoneId)->update(['thana_id' => $thanaId]);
            DB::table('shippings')->where('zone_id', $zoneId)->update(['thana_id' => $thanaId]);
        }

        // Keep the encoded vendor OrderController's legacy field synchronized.
        DB::table('shippings')->whereNotNull('thana_id')->update(['upazila_id' => DB::raw('thana_id')]);

        // Incomplete checkouts store their delivery selection inside the items JSON.
        // Rewrite them before the legacy tables disappear so older checkouts retain
        // their canonical delivery location when an admin accepts the order.
        DB::table('incomplete_orders')->orderBy('id')->chunkById(100, function ($orders) use ($zoneToThana) {
            foreach ($orders as $order) {
                $payload = json_decode((string) $order->items, true);
                $meta = is_array($payload) && isset($payload['meta']) && is_array($payload['meta'])
                    ? $payload['meta']
                    : null;

                if ($meta === null) {
                    continue;
                }

                $thanaId = null;
                if (! empty($meta['zone_id']) && isset($zoneToThana[(int) $meta['zone_id']])) {
                    $thanaId = $zoneToThana[(int) $meta['zone_id']];
                } elseif (! empty($meta['upazila_id'])) {
                    $thanaId = (int) $meta['upazila_id'];
                }

                if (! $thanaId) {
                    continue;
                }

                $payload['meta']['thana_id'] = $thanaId;
                unset($payload['meta']['zone_id'], $payload['meta']['upazila_id']);

                DB::table('incomplete_orders')->where('id', $order->id)->update([
                    'items' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        });

        foreach (['customers', 'customer_addresses', 'shippings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign('thana_id', $tableName.'_thana_id_foreign')
                    ->references('id')->on('thanas')->nullOnDelete();
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['zone_id', 'upazila_id']);
        });
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });

        Schema::drop('delivery_zones');

        // The vendor-supplied, ionCube-encoded OrderController still performs
        // read-only queries against `upazilas`. Keep a compatibility view while
        // `thanas` remains the single writable location table.
        DB::statement('CREATE VIEW upazilas AS SELECT id, district_id, name, sort_order, status, created_at, updated_at FROM thanas');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS upazilas');

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('district_id')->index();
            $table->string('name', 190);
            $table->string('name_bn', 190)->nullable();
            $table->string('post_code', 20)->nullable();
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->boolean('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnDelete();
        });

        DB::table('thanas')->orderBy('id')->chunkById(100, function ($thanas) {
            DB::table('delivery_zones')->insert($thanas->map(fn ($thana) => [
                'id' => $thana->id,
                'district_id' => $thana->district_id,
                'name' => $thana->name,
                'name_bn' => $thana->name_bn,
                'post_code' => $thana->post_code,
                'delivery_charge' => $thana->delivery_charge,
                'status' => $thana->status,
                'sort_order' => $thana->sort_order,
                'created_at' => $thana->created_at,
                'updated_at' => $thana->updated_at,
            ])->all());
        });

        foreach (['customers', 'customer_addresses', 'shippings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign($tableName.'_thana_id_foreign');
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('zone_id')->nullable()->index();
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->unsignedInteger('zone_id')->nullable()->index();
            $table->unsignedInteger('upazila_id')->nullable();
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });
        Schema::table('shippings', function (Blueprint $table) {
            $table->unsignedInteger('zone_id')->nullable()->index();
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });

        DB::table('customer_addresses')->whereNotNull('thana_id')->update(['upazila_id' => DB::raw('thana_id')]);
        DB::table('shippings')->whereNotNull('thana_id')->update(['upazila_id' => DB::raw('thana_id')]);
        DB::table('customers')->whereNotNull('thana_id')->update(['zone_id' => DB::raw('thana_id')]);
        DB::table('customer_addresses')->whereNotNull('thana_id')->update(['zone_id' => DB::raw('thana_id')]);
        DB::table('shippings')->whereNotNull('thana_id')->update(['zone_id' => DB::raw('thana_id')]);

        DB::table('incomplete_orders')->orderBy('id')->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                $payload = json_decode((string) $order->items, true);
                if (! is_array($payload) || ! isset($payload['meta']['thana_id'])) {
                    continue;
                }

                $payload['meta']['zone_id'] = $payload['meta']['thana_id'];
                unset($payload['meta']['thana_id']);
                DB::table('incomplete_orders')->where('id', $order->id)->update([
                    'items' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        });

        foreach (['customers', 'customer_addresses', 'shippings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('thana_id');
            });
        }

        Schema::table('thanas', function (Blueprint $table) {
            $table->dropColumn(['name_bn', 'post_code', 'delivery_charge']);
        });
        Schema::rename('thanas', 'upazilas');
    }
};
