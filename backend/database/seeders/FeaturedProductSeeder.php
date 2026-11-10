<?php

namespace Database\Seeders;

use App\Models\FeaturedProduct;
use Illuminate\Database\Seeder;

/**
 * Seeds the 5 products the business has chosen as "top rated" for the
 * home page. Fake Store IDs, picked to cover every category.
 * Change the list here (or the featured_products table) to re-curate.
 */
class FeaturedProductSeeder extends Seeder
{
    /**
     * Fake Store product IDs, in display order.
     *
     * @var list<int>
     */
    private const PRODUCT_IDS = [1, 5, 9, 14, 18];

    public function run(): void
    {
        foreach (self::PRODUCT_IDS as $index => $productId) {
            FeaturedProduct::query()->updateOrCreate(
                ['product_id' => $productId],
                ['position' => $index + 1],
            );
        }
    }
}
