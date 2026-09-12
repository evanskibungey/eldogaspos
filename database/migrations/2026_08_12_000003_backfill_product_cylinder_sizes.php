<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cylinder size previously existed only as free text inside product names
     * ("Refill 13KG", "22.5kg Outright"). This lifts it into the structured
     * column so stock can be tracked and reported per size.
     *
     * Detection is by PRODUCT NAME, not category. On this system categories are
     * organised by size and sale type ("Refill 6kg", "13kg Outright"), and they
     * do not reliably separate cylinders from accessories - "22.5kg Outright" is
     * a genuine 12,000/- cylinder filed under Gas Accessories, while "13KG
     * Regulator" and "6Kg Meko Grill" sit in the same category and are not
     * cylinders at all.
     *
     * So the rule is: a product is a cylinder when its name carries a kg figure
     * AND does not name an accessory. Accessories are excluded by keyword
     * because they are the things that legitimately quote a cylinder size while
     * not being one - a regulator for 13kg cylinders is still a regulator.
     */
    public function up(): void
    {
        $products = DB::table('products')
            ->whereNull('cylinder_size_kg')
            ->get(['id', 'name']);

        foreach ($products as $product) {
            $size = $this->resolveSizeKg($product->name);

            if ($size !== null) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['cylinder_size_kg' => $size]);
            }
        }
    }

    public function down(): void
    {
        DB::table('products')->update(['cylinder_size_kg' => null]);
    }

    /**
     * Words that mean "this is equipment for a cylinder", not a cylinder.
     * Matched case-insensitively anywhere in the product name.
     */
    private const ACCESSORY_KEYWORDS = [
        'regulator', 'grill', 'hose', 'pipe', 'burner', 'stove', 'cooker',
        'adapter', 'adaptor', 'valve', 'detector', 'glove', 'extinguisher',
        'stand', 'cart', 'lighter', 'apron', 'tray', 'gauge', 'spanner',
        'trolley', 'cap', 'seal', 'washer', 'grille', 'cooktop', 'lamp',
    ];

    /**
     * The kg figure for a product, or null when it is not a cylinder.
     */
    public function resolveSizeKg(string $name): ?float
    {
        if ($this->looksLikeAccessory($name)) {
            return null;
        }

        // "13KG", "6kg", "22.5 kg", "3Kg"
        if (preg_match('/(\d+(?:\.\d+)?)\s*kg\b/i', $name, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    public function looksLikeAccessory(string $name): bool
    {
        $haystack = strtolower($name);

        foreach (self::ACCESSORY_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }
};
