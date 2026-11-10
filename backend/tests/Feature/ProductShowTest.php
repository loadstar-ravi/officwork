<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * GIVEN I am browsing the products
 * WHEN I click on a product tile
 * THEN I see the product details page
 */
class ProductShowTest extends TestCase
{
    public function test_returns_the_product_details(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products/9')
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => 9,
                // Trailing space from the source is trimmed.
                'title' => 'WD 2TB Elements Portable External Hard Drive - USB 3.0',
                'description' => 'USB 3.0 and USB 2.0 compatibility. Fast data transfers.',
                'price' => 64,
                'image_url' => 'https://fakestoreapi.com/img/61IBBVJvSDL._AC_SY879_t.png',
                'category' => ['slug' => 'electronics', 'name' => 'electronics'],
                'rating' => ['rate' => 3.3, 'count' => 203],
            ]]);
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products/999')->assertNotFound();
    }

    public function test_non_numeric_product_id_returns_404(): void
    {
        $this->getJson('/api/v1/products/abc')->assertNotFound();
    }
}
