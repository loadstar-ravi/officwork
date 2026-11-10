<?php

namespace Tests\Feature;

use App\Models\FeaturedProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GIVEN I have chosen 5 products to be "top rated"
 * WHEN I am viewing the home page
 * THEN I see those 5 featured products
 */
class FeaturedProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_chosen_featured_products_in_their_chosen_order(): void
    {
        $this->fakeProductSource();
        $this->feature([18, 3, 9, 5, 1]);

        $response = $this->getJson('/api/v1/featured-products');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.*.id', [18, 3, 9, 5, 1])
            ->assertJsonStructure(['data' => [[
                'id', 'title', 'description', 'price', 'image_url',
                'category' => ['slug', 'name'],
                'rating' => ['rate', 'count'],
            ]]]);
    }

    public function test_never_returns_more_than_the_featured_limit(): void
    {
        $this->fakeProductSource();
        $this->feature([1, 2, 3, 4, 5, 9]);

        $this->getJson('/api/v1/featured-products')
            ->assertOk()
            ->assertJsonPath('data.*.id', [1, 2, 3, 4, 5]);
    }

    public function test_skips_featured_products_that_no_longer_exist_in_the_source(): void
    {
        $this->fakeProductSource();
        $this->feature([1, 999, 3]);

        $this->getJson('/api/v1/featured-products')
            ->assertOk()
            ->assertJsonPath('data.*.id', [1, 3]);
    }

    public function test_returns_an_empty_list_when_nothing_is_featured(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/featured-products')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @param  list<int>  $productIds  In display order.
     */
    private function feature(array $productIds): void
    {
        foreach ($productIds as $index => $productId) {
            FeaturedProduct::factory()->create([
                'product_id' => $productId,
                'position' => $index + 1,
            ]);
        }
    }
}
