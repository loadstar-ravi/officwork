<?php

namespace App\Providers;

use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Sources\FakeStoreProductRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class CatalogueServiceProvider extends ServiceProvider
{
    /**
     * Swapping the product source (database, Shopify, search service...)
     * means binding a different ProductRepository here. Nothing else changes.
     */
    public function register(): void
    {
        $this->app->singleton(ProductRepository::class, fn (Application $app): ProductRepository => new FakeStoreProductRepository(
            http: $app->make(Http::class),
            cache: $app->make(Cache::class),
            baseUrl: config('catalogue.fakestore.base_url'),
            timeoutSeconds: (int) config('catalogue.fakestore.timeout_seconds'),
            cacheTtlSeconds: (int) config('catalogue.fakestore.cache_ttl_seconds'),
        ));
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));

        RateLimiter::for('enquiries', fn (Request $request): Limit => Limit::perMinute(
            (int) config('catalogue.enquiries.per_minute'),
        )->by($request->ip()));
    }
}
