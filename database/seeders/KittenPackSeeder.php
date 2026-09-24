<?php

namespace Database\Seeders;

use App\Models\KittenPack;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KittenPackSeeder extends Seeder
{
    /**
     * Content transcribed from the "Kitten Packs" Figma frame (node 1593-20240).
     *
     * Every pack lists the same 15 items; `false` marks the rows the design
     * strikes through, meaning that item is not in that particular pack.
     */
    public function run(): void
    {
        // name, then [included?, quantity] per pack: 1 Week / 2 Weeks / 1 Month.
        $matrix = [
            ['Taipet Premium Kitten Dry 400g',   [true, 1],  [true, 2],  [false, 1]],
            ['Jungle Kitten Dry 1.5kg',          [false, 1], [false, 1], [true, 1]],
            ['PawPaw Kitten Can 400g',           [true, 1],  [true, 2],  [true, 4]],
            ['Whiskas Junior Tuna 80g',          [true, 1],  [true, 2],  [true, 4]],
            ['Nekko Kitten Chicken Mousse 70g',  [true, 1],  [true, 2],  [true, 3]],
            ['Nekko Kitten Tuna & Salmon 70g',   [true, 1],  [true, 1],  [true, 3]],
            ['Taipet Creamy Treats, 5 tubes',    [true, 1],  [true, 1],  [true, 1]],
            ['Wanpy Creamy Treats, 5 tubes',     [false, 1], [false, 1], [true, 1]],
            ['Cat Grass Teething Sticks',        [false, 1], [false, 1], [true, 1]],
            ['Dr. Clump Litter 10L',             [false, 1], [true, 1],  [true, 1]],
            ['Paw Print Collar with Bell',       [true, 1],  [true, 1],  [false, 1]],
            ['Pearl Bow Collar with Bell',       [false, 1], [false, 1], [true, 1]],
            ['Flea Removal Comb',                [true, 1],  [true, 1],  [true, 1]],
            ['Grooming Glove',                   [false, 1], [false, 1], [true, 1]],
            ['Helminticide-L Dewormer',          [false, 1], [false, 1], [true, 1]],
        ];

        $packs = [
            [
                'name'       => 'Welcome Home Kit - 1 Week Pack',
                'tier_label' => 'Starter',
                'badge'      => null,
                'theme'      => 'light',
                'image'      => 'frontEnd/images/kitten-packs/pack-1-week.webp',
                'price'      => 949,
                'old_price'  => 1015,
                'sort_order' => 1,
            ],
            [
                'name'       => 'Welcome Home Kit - 2 Weeks Pack',
                'tier_label' => 'Affordable',
                'badge'      => 'Most Popular',
                'theme'      => 'dark',
                'image'      => 'frontEnd/images/kitten-packs/pack-2-weeks.webp',
                'price'      => 2099,
                'old_price'  => 2215,
                'sort_order' => 2,
            ],
            [
                'name'       => 'Nawabi Kitten Box - 1 Month Pack',
                'tier_label' => 'Premium',
                'badge'      => null,
                'theme'      => 'light',
                'image'      => 'frontEnd/images/kitten-packs/pack-1-month.webp',
                'price'      => 3999,
                'old_price'  => 4145,
                'sort_order' => 3,
            ],
        ];

        foreach ($packs as $index => $attributes) {
            $pack = KittenPack::updateOrCreate(
                ['slug' => Str::slug($attributes['name'])],
                $attributes + ['slug' => Str::slug($attributes['name']), 'status' => 1]
            );

            $pack->items()->delete();

            foreach ($matrix as $row => $line) {
                [$included, $quantity] = $line[$index + 1];

                $pack->items()->create([
                    'name'        => $line[0],
                    'quantity'    => $quantity,
                    'is_included' => $included,
                    'sort_order'  => $row + 1,
                ]);
            }
        }
    }
}
