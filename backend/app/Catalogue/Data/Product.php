<?php

namespace App\Catalogue\Data;

use Illuminate\Support\Str;

/**
 * A product as the rest of the app sees it, whatever source it came from.
 *
 * Sources map their own payloads into this shape, so swapping the product
 * source never ripples into controllers, resources or the frontend.
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public float $price,
        public string $categoryName,
        public string $imageUrl,
        public float $rating,
        public int $ratingCount,
    ) {}

    public function category(): Category
    {
        return Category::fromName($this->categoryName);
    }

    /**
     * Case-insensitive match against the text a customer can see.
     */
    public function matches(string $term): bool
    {
        foreach ([$this->title, $this->description, $this->categoryName] as $field) {
            if (Str::contains($field, $term, ignoreCase: true)) {
                return true;
            }
        }

        return false;
    }
}
