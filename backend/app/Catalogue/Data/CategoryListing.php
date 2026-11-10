<?php

namespace App\Catalogue\Data;

use Illuminate\Support\Collection;

final readonly class CategoryListing
{
    /**
     * @param  Collection<int, Product>  $topRated
     * @param  Collection<int, Product>  $otherProducts  Products in the category that are not in $topRated.
     */
    public function __construct(
        public Category $category,
        public Collection $topRated,
        public Collection $otherProducts,
    ) {}
}
