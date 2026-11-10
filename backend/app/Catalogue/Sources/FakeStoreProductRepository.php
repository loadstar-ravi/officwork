<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Data\Category;
use App\Catalogue\Data\Product;
use App\Exceptions\Catalogue\ProductSourceUnavailableException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Reads products from the Fake Store API (https://fakestoreapi.com).
 *
 * Fake Store has only ~20 products, so the whole list is fetched in one
 * call, cached, and filtered in memory. A larger source would push
 * filtering and search down to the source instead; callers would not change.
 */
final class FakeStoreProductRepository implements ProductRepository
{
    public const CACHE_KEY = 'catalogue.fakestore.products';

    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
        private readonly int $cacheTtlSeconds,
    ) {}

    public function all(): Collection
    {
        // Cache the raw payload rather than Product objects so a code
        // change to Product never meets stale serialized objects.
        $payload = $this->cache->remember(
            self::CACHE_KEY,
            $this->cacheTtlSeconds,
            fn (): array => $this->fetchProducts(),
        );

        return collect($payload)
            ->map(fn (mixed $item): ?Product => $this->toProduct($item))
            ->filter()
            ->values();
    }

    public function find(int $id): ?Product
    {
        // Looked up from the cached list on purpose: Fake Store answers
        // GET /products/{unknown-id} with HTTP 200 and an empty body.
        return $this->all()->first(fn (Product $product): bool => $product->id === $id);
    }

    public function findMany(array $ids): Collection
    {
        $productsById = $this->all()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?Product => $productsById->get($id))
            ->filter()
            ->values();
    }

    public function categories(): Collection
    {
        return $this->all()
            ->map(fn (Product $product): Category => $product->category())
            ->unique('slug')
            ->sortBy('name')
            ->values();
    }

    public function inCategory(string $slug): Collection
    {
        return $this->all()
            ->filter(fn (Product $product): bool => $product->category()->slug === $slug)
            ->values();
    }

    public function search(string $term): Collection
    {
        return $this->all()
            ->filter(fn (Product $product): bool => $product->matches($term))
            ->values();
    }

    /**
     * @return array<int, mixed>
     */
    private function fetchProducts(): array
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeoutSeconds)
                ->retry(2, 200, throw: false)
                ->get('/products');
        } catch (ConnectionException $exception) {
            throw new ProductSourceUnavailableException('Fake Store API is unreachable.', previous: $exception);
        }

        $payload = $response->json();

        if ($response->failed() || ! is_array($payload) || ! array_is_list($payload)) {
            throw new ProductSourceUnavailableException(
                "Fake Store API returned an unusable response (HTTP {$response->status()}).",
            );
        }

        return $payload;
    }

    /**
     * Map one Fake Store item to a Product, or null if it is malformed.
     * One bad item should not take down the whole catalogue.
     */
    private function toProduct(mixed $item): ?Product
    {
        $validator = Validator::make(is_array($item) ? $item : [], [
            'id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string'],
            'image' => ['required', 'string'],
            'rating.rate' => ['nullable', 'numeric'],
            'rating.count' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            Log::warning('Skipping malformed Fake Store product.', [
                'id' => is_array($item) ? ($item['id'] ?? null) : null,
                'errors' => $validator->errors()->all(),
            ]);

            return null;
        }

        return new Product(
            id: (int) $item['id'],
            title: trim($item['title']),
            description: trim($item['description'] ?? ''),
            price: (float) $item['price'],
            categoryName: trim($item['category']),
            imageUrl: $item['image'],
            rating: (float) ($item['rating']['rate'] ?? 0),
            ratingCount: (int) ($item['rating']['count'] ?? 0),
        );
    }
}
