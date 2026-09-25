<?php

namespace App\Console\Commands;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class InventoryInitialize extends Command
{
    protected $signature = 'inventory:initialize';

    protected $description = 'Backfill inventory rows from existing product stock (idempotent — already-tracked products are skipped)';

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;

        Product::query()->orderBy('id')->chunkById(200, function ($products) use (&$created, &$skipped) {
            foreach ($products as $product) {
                if (InventoryStock::where('product_id', $product->id)->exists()) {
                    $skipped++;
                    continue;
                }

                $row = InventoryService::seedProduct($product);
                $this->line(sprintf(
                    '  #%d %s — on_hand %d, reserved %d, available %d',
                    $product->id,
                    \Illuminate\Support\Str::limit($product->name, 48),
                    $row->on_hand,
                    $row->reserved,
                    $row->available
                ));
                $created++;
            }
        });

        $this->info("Inventory initialized: {$created} product(s) seeded, {$skipped} already tracked.");

        return self::SUCCESS;
    }
}
