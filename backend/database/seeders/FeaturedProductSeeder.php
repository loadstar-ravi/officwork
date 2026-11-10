<?php

namespace Database\Seeders;

use App\Models\FeaturedProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sets the 5 products the business has chosen as "top rated" for the
 * home page. Fake Store IDs, picked to cover every category.
 *
 * To re-curate, change PRODUCT_IDS and run:
 *   php artisan db:seed --class=FeaturedProductSeeder
 * The table is synced to this list, so products removed from it stop
 * being featured.
 */
class FeaturedProductSeeder extends Seeder
{
    /**
     * Fake Store product IDs, in display order.
     *
     * @var list<int>
     */
    public const PRODUCT_IDS = [1, 5, 9, 14, 18];

    public function run(): void
    {
        DB::transaction(function (): void {
            FeaturedProduct::query()->whereNotIn('product_id', self::PRODUCT_IDS)->delete();

            foreach (self::PRODUCT_IDS as $index => $productId) {
                FeaturedProduct::query()->updateOrCreate(
                    ['product_id' => $productId],
                    ['position' => $index + 1],
                );
            }
        });
    }
}
