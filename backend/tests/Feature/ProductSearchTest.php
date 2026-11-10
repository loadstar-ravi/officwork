<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * GIVEN I am looking for a specific product
 * AND I have entered a search term in the search bar
 * WHEN I click the search button
 * THEN I only see products that have text matching that search term
 */
class ProductSearchTest extends TestCase
{
    /**
     * @return array<string, array{string, list<int>}>
     */
    public static function searchTerms(): array
    {
        return [
            'matches title' => ['jacket', [3]],
            'ignores case' => ['JACKET', [3]],
            'matches description' => ['usb 2.0', [9]],
            'matches category' => ['jewelery', [5]],
            'matches across products' => ['slim', [2, 4]],
        ];
    }

    /**
     * @param  list<int>  $expectedIds
     */
    #[DataProvider('searchTerms')]
    public function test_only_returns_products_with_text_matching_the_term(string $term, array $expectedIds): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products?'.http_build_query(['search' => $term]))
            ->assertOk()
            ->assertJsonPath('data.*.id', $expectedIds);
    }

    public function test_returns_an_empty_list_when_nothing_matches(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products?search=trampoline')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_surrounding_whitespace_is_ignored(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products?search='.urlencode('  jacket  '))
            ->assertOk()
            ->assertJsonPath('data.*.id', [3]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTerms(): array
    {
        return [
            'empty' => [''],
            'one character' => ['a'],
            'too long' => [str_repeat('a', 101)],
        ];
    }

    #[DataProvider('invalidTerms')]
    public function test_rejects_invalid_search_terms(string $term): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products?'.http_build_query(['search' => $term]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_lists_every_product_when_no_term_is_given(): void
    {
        $this->fakeProductSource();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(8, 'data');
    }
}
