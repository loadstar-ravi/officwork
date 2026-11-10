<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * GIVEN my products are organised into categories
 * WHEN I click on a category link
 * THEN I see top rated products for that category
 * AND I see additional products in that category
 */
class CategoryTest extends TestCase
{
    public function test_lists_categories_with_url_safe_slugs(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['slug' => 'electronics', 'name' => 'electronics'],
                ['slug' => 'jewelery', 'name' => 'jewelery'],
                ['slug' => 'mens-clothing', 'name' => "men's clothing"],
                ['slug' => 'womens-clothing', 'name' => "women's clothing"],
            ]]);
    }

    public function test_shows_the_top_rated_products_then_the_rest_of_the_category(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/categories/mens-clothing')
            ->assertOk()
            ->assertJsonPath('data.category', ['slug' => 'mens-clothing', 'name' => "men's clothing"])
            // Ratings in the fixture: 3 => 4.7, 2 => 4.1, 1 => 3.9, 4 => 2.1
            ->assertJsonPath('data.top_rated.*.id', [3, 2, 1])
            ->assertJsonPath('data.other_products.*.id', [4]);
    }

    public function test_only_includes_products_from_that_category(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/categories/electronics')
            ->assertOk()
            ->assertJsonPath('data.top_rated.*.id', [9, 14])
            ->assertJsonPath('data.other_products', []);
    }

    public function test_breaks_rating_ties_by_number_of_reviews(): void
    {
        $products = $this->productFixture();
        $products[] = [...$products[0], 'id' => 50, 'rating' => ['rate' => 4.7, 'count' => 900]];
        $this->fakeProductSource($products);

        $this->getJson('/api/v1/categories/mens-clothing')
            ->assertOk()
            ->assertJsonPath('data.top_rated.*.id', [50, 3, 2]);
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/categories/garden-furniture')->assertNotFound();
    }
}
