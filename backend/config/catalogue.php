<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product source: Fake Store API
    |--------------------------------------------------------------------------
    |
    | Products are cached so the catalogue stays fast and keeps working
    | through short Fake Store outages within the TTL.
    |
    */

    'fakestore' => [
        'base_url' => env('FAKESTORE_BASE_URL', 'https://fakestoreapi.com'),
        'timeout_seconds' => (int) env('FAKESTORE_TIMEOUT_SECONDS', 5),
        'cache_ttl_seconds' => (int) env('CATALOGUE_CACHE_TTL_SECONDS', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Merchandising
    |--------------------------------------------------------------------------
    */

    // Featured ("top rated") products picked by the business for the home page.
    'featured_limit' => 5,

    // Highest-rated products highlighted at the top of each category page.
    'category_top_rated_limit' => 3,

    /*
    |--------------------------------------------------------------------------
    | Enquiries
    |--------------------------------------------------------------------------
    */

    'enquiries' => [
        'sales_inbox' => env('SALES_INBOX_ADDRESS', 'sales@example.com'),
        'per_minute' => (int) env('ENQUIRIES_PER_MINUTE', 5),
    ],

];
