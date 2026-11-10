<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * How the API behaves when the product source misbehaves.
 */
class ProductSourceTest extends TestCase
{
    public function test_returns_503_when_the_source_errors(): void
    {
        Http::fake(['fakestoreapi.com/*' => Http::response('Server error', 500)]);

        $this->getJson('/api/v1/products')
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Products are temporarily unavailable. Please try again shortly.');
    }

    public function test_returns_503_when_the_source_is_unreachable(): void
    {
        Http::fake(['fakestoreapi.com/*' => Http::failedConnection()]);

        $this->getJson('/api/v1/categories')->assertServiceUnavailable();
    }

    public function test_returns_503_when_the_source_returns_something_other_than_a_list(): void
    {
        Http::fake(['fakestoreapi.com/*' => Http::response(['error' => 'nope'])]);

        $this->getJson('/api/v1/products')->assertServiceUnavailable();
    }

    public function test_retries_a_failed_request_before_giving_up(): void
    {
        Http::fake(['fakestoreapi.com/*' => Http::sequence()
            ->push('Server error', 500)
            ->push($this->productFixture()),
        ]);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(8, 'data');
    }

    public function test_caches_products_so_repeat_requests_do_not_hit_the_source(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products')->assertOk();
        $this->getJson('/api/v1/products/3')->assertOk();
        $this->getJson('/api/v1/categories')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_does_not_cache_failures(): void
    {
        Http::fake(['fakestoreapi.com/*' => Http::sequence()
            ->push('Server error', 500)
            ->push('Server error', 500)
            ->push('Server error', 500)
            ->push($this->productFixture()),
        ]);

        $this->getJson('/api/v1/products')->assertServiceUnavailable();
        $this->getJson('/api/v1/products')->assertOk();
    }

    public function test_skips_malformed_products_instead_of_failing_the_whole_catalogue(): void
    {
        $products = $this->productFixture();
        $products[] = ['id' => 'not-a-number', 'title' => 'Broken'];
        $products[] = 'not even an object';
        $this->fakeProductSource($products);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(8, 'data');
    }
}
