<?php

namespace Tests\Feature;

use App\Models\FeaturedProduct;
use Database\Seeders\FeaturedProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_chosen_products_in_order(): void
    {
        $this->seed(FeaturedProductSeeder::class);

        $this->assertSame(
            FeaturedProductSeeder::PRODUCT_IDS,
            FeaturedProduct::query()->orderBy('position')->pluck('product_id')->all(),
        );
    }

    public function test_re_seeding_replaces_the_previous_selection_instead_of_adding_to_it(): void
    {
        FeaturedProduct::factory()->create(['product_id' => 2, 'position' => 1]);

        $this->seed(FeaturedProductSeeder::class);
        $this->seed(FeaturedProductSeeder::class);

        $this->assertDatabaseMissing('featured_products', ['product_id' => 2]);
        $this->assertDatabaseCount('featured_products', count(FeaturedProductSeeder::PRODUCT_IDS));
    }
}
