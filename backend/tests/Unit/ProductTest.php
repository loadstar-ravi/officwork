<?php

namespace Tests\Unit;

use App\Catalogue\Data\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function terms(): array
    {
        return [
            'title' => ['jacket', true],
            'description' => ['outerwear', true],
            'category' => ["men's", true],
            'case-insensitive' => ['COTTON', true],
            'no match' => ['trampoline', false],
        ];
    }

    #[DataProvider('terms')]
    public function test_matches_terms_in_visible_text(string $term, bool $expected): void
    {
        $product = new Product(
            id: 3,
            title: 'Mens Cotton Jacket',
            description: 'Great outerwear for Spring.',
            price: 55.99,
            categoryName: "men's clothing",
            imageUrl: 'https://example.com/jacket.png',
            rating: 4.7,
            ratingCount: 500,
        );

        $this->assertSame($expected, $product->matches($term));
    }

    public function test_category_slug_is_url_safe(): void
    {
        $product = new Product(1, 'T', 'D', 1.0, "women's clothing", 'https://example.com/i.png', 4.0, 1);

        $this->assertSame('womens-clothing', $product->category()->slug);
        $this->assertSame("women's clothing", $product->category()->name);
    }
}
