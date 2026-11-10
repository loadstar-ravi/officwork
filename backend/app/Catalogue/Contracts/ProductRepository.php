<?php

namespace App\Catalogue\Contracts;

use App\Catalogue\Data\Category;
use App\Catalogue\Data\Product;
use App\Exceptions\Catalogue\ProductSourceUnavailableException;
use Illuminate\Support\Collection;

/**
 * The boundary between the app and wherever product data lives.
 *
 * Today the only implementation wraps the Fake Store API. A database,
 * a headless commerce platform or a search service can replace it by
 * implementing this interface and changing one binding in
 * CatalogueServiceProvider.
 *
 * Every method throws ProductSourceUnavailableException when the
 * source can't be reached, so callers can handle one failure type.
 */
interface ProductRepository
{
    /**
     * @return Collection<int, Product>
     *
     * @throws ProductSourceUnavailableException
     */
    public function all(): Collection;

    /**
     * @throws ProductSourceUnavailableException
     */
    public function find(int $id): ?Product;

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Product> Products in the order of $ids; unknown IDs are skipped.
     *
     * @throws ProductSourceUnavailableException
     */
    public function findMany(array $ids): Collection;

    /**
     * @return Collection<int, Category>
     *
     * @throws ProductSourceUnavailableException
     */
    public function categories(): Collection;

    /**
     * @return Collection<int, Product>
     *
     * @throws ProductSourceUnavailableException
     */
    public function inCategory(string $slug): Collection;

    /**
     * @return Collection<int, Product> Products whose visible text contains $term.
     *
     * @throws ProductSourceUnavailableException
     */
    public function search(string $term): Collection;
}
