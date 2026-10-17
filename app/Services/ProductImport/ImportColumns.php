<?php

namespace App\Services\ProductImport;

/**
 * The columns of the product import workbook, used by the template, the reader and the
 * Instructions sheet so they never drift apart.
 *
 * Each column: key, header, required, width, list (named range on the Lists sheet), strict
 * (only list values allowed), format, allowed values, example, what happens when left blank.
 */
class ImportColumns
{
    public const SHEET_PRODUCTS = 'Products';
    public const SHEET_VARIANTS = 'Variants';
    public const SHEET_WHOLESALE = 'Wholesale';

    public const BADGES = ['Best Seller', 'Limited Offer', 'New Arrival', 'Top Rated', 'Exclusive Deal', 'Hot Deal'];
    public const UNITS = ['pcs', 'pack', 'box', 'pouch', 'can', 'bag', 'bottle', 'kg', 'g', 'ltr'];
    public const TYPES = ['Physical'];

    /** @return array<string, array<int, array<string, mixed>>> sheet => columns */
    public static function sheets(): array
    {
        return [
            self::SHEET_PRODUCTS => self::products(),
            self::SHEET_VARIANTS => self::variants(),
            self::SHEET_WHOLESALE => self::wholesale(),
        ];
    }

    public static function products(): array
    {
        $yes = ['list' => 'YesNo', 'strict' => true, 'allowed' => 'Yes, No (also 1/0, TRUE/FALSE)'];

        return [
            self::col('sku', 'SKU', false, 16, 'Text, unique in the file', 'Your own code (e.g. BG-WHI-OF80) or an existing product code (P0241)', 'BG-WHI-OF80', 'New product: a code like P0241 is created. Needed to link Variants/Wholesale rows and to update a product.'),
            self::col('name', 'Product Name', true, 46, 'Text (up to ~120 characters recommended)', '', 'Whiskas Adult Pouch Ocean Fish Flavor Wet Cat Food – 80g', '— (required)'),
            self::col('short_name', 'Short Name', false, 26, 'Text, max 40 characters', 'Printed on invoices', 'Whiskas Ocean Fish', 'Made from the name, like the form\'s Auto-generate button.'),
            self::col('category', 'Category', true, 20, 'Name from the list', 'See Lists sheet', 'Cat Food', '— (required for new products)', ['list' => 'Categories', 'strict' => true]),
            self::col('subcategory', 'SubCategory', false, 18, 'Name from the list; must belong to the Category', 'See Lists sheet (column "Subcategory belongs to category")', 'Pouch', 'Left empty.', ['list' => 'Subcategories', 'strict' => true]),
            self::col('childcategory', 'Child Category', false, 16, 'Name from the list; must belong to the SubCategory', 'See Lists sheet', '', 'Left empty.', ['list' => 'ChildCategories', 'strict' => true]),
            self::col('description', 'Description', false, 50, 'Plain text or HTML. Plain text: blank line = new paragraph, lines starting "- " or "• " = bullet list, a line ending with ":" = bold heading', '', "Complete wet food for adult cats.\n\nKey benefits:\n- Real ocean fish", 'Warning only; the meta description is then built from name, brand, weight and category.'),
            self::col('note', 'Note', false, 22, 'Text (internal only)', '', 'Supplier: ABC Traders', 'Left empty.'),
            self::col('purchase_price', 'Purchase Price', false, 13, 'Number in BDT, no ৳ sign', '0 or more', '70', '0'),
            self::col('old_price', 'Old Price', false, 11, 'Number in BDT', '0 or more (warning if lower than New Price)', '95', 'Left empty (no strike-through price).'),
            self::col('new_price', 'New Price', true, 11, 'Number in BDT', 'More than 0', '85', '— (required for new products)'),
            self::col('stock', 'Stock', false, 9, 'Whole number', '0 or more', '48', '0. Recorded in the stock log as opening stock. In update mode stock only changes if "Update stock from file" is ticked.'),
            self::col('unit', 'Unit', false, 9, 'Text', 'e.g. pcs, pouch, pack (others allowed)', 'pouch', 'Left empty.', ['list' => 'Units', 'strict' => false]),
            self::col('brand', 'Brand', false, 14, 'Name from the list', 'See Lists sheet', 'Whiskas', 'Left empty. A name not in the list is an error, unless "Auto-create missing items" is ticked.', ['list' => 'Brands', 'strict' => false]),
            self::col('weight', 'Weight', false, 11, 'Name from the list', 'See Lists sheet', '80 gm', 'Left empty. Unknown: error or auto-create (as Brand).', ['list' => 'Weights', 'strict' => false]),
            self::col('life_stage', 'Life Stage', false, 11, 'Name from the list', 'See Lists sheet', 'Adult', 'Left empty. Unknown: error or auto-create.', ['list' => 'LifeStages', 'strict' => false]),
            self::col('flavor', 'Flavor', false, 16, 'Name from the list', 'See Lists sheet', 'Ocean Fish', 'Left empty. Unknown: error or auto-create.', ['list' => 'Flavors', 'strict' => false]),
            self::col('images', 'Gallery Images', false, 40, 'Image URLs (https://…) OR file names from the uploaded ZIP, separated by |', 'JPG, PNG, WebP, GIF; max 5 MB each', 'https://example.com/whiskas-of-1.jpg|https://example.com/whiskas-of-2.jpg', 'Warning: product has no images.'),
            self::col('image_alts', 'Gallery Image Alt Texts', false, 34, 'One alt text per image, same order, separated by |', '', 'Whiskas Ocean Fish pouch front|Whiskas Ocean Fish pouch back', '"{Name}", then "{Name} – back view", "– side view", "– detail view", "– image 5"…'),
            self::col('video', 'Video (YouTube URL)', false, 26, 'YouTube link or video ID', 'YouTube only (no video files)', 'https://youtu.be/dQw4w9WgXcQ', 'No video.'),
            self::col('type', 'Type', false, 10, 'From the list', 'Physical', 'Physical', 'Physical', ['list' => 'Types', 'strict' => true]),
            self::col('advance', 'Advance', false, 9, 'Number in BDT', '0 or more', '0', '0'),
            self::col('free_delivery', 'Free Delivery', false, 11, 'Yes/No', '', 'No', 'No', $yes),
            self::col('status', 'Status', false, 9, 'Yes/No (Yes = visible on the site)', '', 'Yes', 'Yes', $yes),
            self::col('hot', 'Hot', false, 7, 'Yes/No', '', 'No', 'No', $yes),
            self::col('flash', 'Flash', false, 7, 'Yes/No', '', 'No', 'No', $yes),
            self::col('best_seller', 'Best Seller', false, 10, 'Yes/No', '', 'Yes', 'No', $yes),
            self::col('sold', 'Sold', false, 8, 'Whole number (shown with Best Seller)', '0 or more', '145', '0'),
            self::col('new_arrival', 'New Arrival', false, 10, 'Yes/No', '', 'No', 'No', $yes),
            self::col('badge', 'Badge', false, 14, 'From the list', implode(', ', self::BADGES), 'Best Seller', 'No badge.', ['list' => 'Badges', 'strict' => true]),
            self::col('wholesale', 'Wholesale', false, 10, 'Yes/No. Price tiers go in the Wholesale sheet', '', 'Yes', 'No', $yes),
            self::col('meta_title', 'Meta Title', false, 34, 'Text, ~60 characters', '', '', '"{Product name} Price in Bangladesh", shortened to about 60 characters.'),
            self::col('meta_keywords', 'Meta Keywords', false, 30, 'Comma-separated', '', '', 'Built from name, brand, category, subcategory, life stage and flavor.'),
            self::col('meta_description', 'Meta Description', false, 40, 'Text, ~155 characters', '', '', 'First ~155 characters of the description\'s text; without a description, a sentence built from name, brand, weight and category.'),
            self::col('meta_image', 'Meta Image', false, 26, 'One image URL or ZIP file name', 'Used as og:image when shared', '', 'The first gallery image.'),
            self::col('meta_image_alt', 'Meta Image Alt', false, 26, 'Text', '', '', 'The product name.'),
        ];
    }

    public static function variants(): array
    {
        return [
            self::col('sku', 'SKU', true, 16, 'SKU of a product (in this file or already in the shop)', '', 'BG-COLLAR-PB', '— (required)'),
            self::col('color', 'Color', false, 16, 'Name from the list. Color, Size or both', 'See Lists sheet', 'Pink', 'Size-only variant. Unknown: error or auto-create.', ['list' => 'Colors', 'strict' => false]),
            self::col('size', 'Size', false, 12, 'Name from the list. Color, Size or both', 'See Lists sheet', 'S', 'Color-only variant. Unknown: error or auto-create.', ['list' => 'Sizes', 'strict' => false]),
            self::col('price', 'Price', true, 10, 'Number in BDT', 'More than 0', '350', '— (required)'),
            self::col('stock', 'Stock', false, 9, 'Whole number', '0 or more', '10', '0'),
            self::col('image', 'Image', false, 34, 'One image URL or ZIP file name (saved only when the variant has a Color, as in the form)', 'JPG, PNG, WebP, GIF; max 5 MB', 'collar-pink.jpg', 'No variant image.'),
            self::col('image_alt', 'Image Alt', false, 30, 'Text', '', 'Pink pearl bow cat collar', '"{Name} – {Color} {Size}"'),
        ];
    }

    public static function wholesale(): array
    {
        return [
            self::col('sku', 'SKU', true, 16, 'SKU of a product with Wholesale = Yes', '', 'BG-WHI-OF80', '— (required)'),
            self::col('min_qty', 'Min Qty', true, 9, 'Whole number', '1 or more', '12', '— (required)'),
            self::col('max_qty', 'Max Qty', false, 9, 'Whole number', 'Empty = no upper limit', '47', 'No upper limit.'),
            self::col('price', 'Wholesale Price', true, 14, 'Number in BDT (per unit)', 'More than 0', '80', '— (required)'),
            self::col('stock', 'Stock', false, 9, 'Whole number', '0 or more', '0', '0'),
        ];
    }

    /** "Product Name *" / "product name" → "product name" (how headers are matched). */
    public static function normalizeHeader(?string $header): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', str_replace('*', '', (string) $header))));
    }

    private static function col(string $key, string $header, bool $required, int $width, string $format, string $allowed, string $example, string $blank, array $extra = []): array
    {
        return array_merge([
            'key' => $key,
            'header' => $header,
            'required' => $required,
            'width' => $width,
            'list' => null,
            'strict' => false,
            'format' => $format,
            'allowed' => $allowed,
            'example' => $example,
            'blank' => $blank,
        ], $extra);
    }
}
