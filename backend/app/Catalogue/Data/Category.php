<?php

namespace App\Catalogue\Data;

use Illuminate\Support\Str;

/**
 * A product category with a URL-safe slug.
 *
 * Source category names can contain spaces and apostrophes
 * (e.g. "men's clothing"), so the API and frontend address
 * categories by slug instead.
 */
final readonly class Category
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}

    public static function fromName(string $name): self
    {
        return new self(Str::slug($name), $name);
    }
}
