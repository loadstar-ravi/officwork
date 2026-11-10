<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never hit the real Fake Store API.
        Http::preventStrayRequests();

        // Skip the real delay between HTTP retries.
        Sleep::fake();
    }

    /**
     * Fake the Fake Store API's /products endpoint.
     *
     * @param  array<int, mixed>|null  $products  Defaults to tests/Fixtures/fakestore-products.json.
     */
    protected function fakeProductSource(?array $products = null): void
    {
        Http::fake([
            'fakestoreapi.com/products' => Http::response($products ?? $this->productFixture()),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function productFixture(): array
    {
        return json_decode(
            file_get_contents(__DIR__.'/Fixtures/fakestore-products.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
