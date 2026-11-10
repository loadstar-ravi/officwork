<?php

namespace App\Catalogue;

use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Data\CategoryListing;
use App\Catalogue\Data\Product;
use App\Models\FeaturedProduct;
use Illuminate\Support\Collection;

/**
 * Merchandising rules on top of the product source.
 *
 * Product data comes from the ProductRepository; which products are
 * featured is a business decision, so it lives in our own database.
 */
class CatalogueService
{
    public function __construct(
        private readonly ProductRepository $products,
    ) {}

    /**
     * The products the business picked as "top rated", in their chosen order.
     * IDs that no longer exist in the source are skipped, not errors.
     *
     * @return Collection<int, Product>
     */
    public function featured(): Collection
    {
        $ids = FeaturedProduct::query()
            ->orderBy('position')
            ->limit((int) config('catalogue.featured_limit'))
            ->pluck('product_id')
            ->all();

        return $this->products->findMany($ids);
    }

    /**
     * A category's highest-rated products, plus everything else in it.
     * Returns null when no category has this slug.
     */
    public function categoryListing(string $slug): ?CategoryListing
    {
        $category = $this->products->categories()->firstWhere('slug', $slug);

        if ($category === null) {
            return null;
        }

        $products = $this->products->inCategory($slug);

        $topRated = $products
            ->sortBy([['rating', 'desc'], ['ratingCount', 'desc']])
            ->take((int) config('catalogue.category_top_rated_limit'))
            ->values();

        $topRatedIds = $topRated->pluck('id')->all();

        $otherProducts = $products
            ->reject(fn (Product $product): bool => in_array($product->id, $topRatedIds, true))
            ->values();

        return new CategoryListing($category, $topRated, $otherProducts);
    }
}
