<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EnquiryController;
use App\Http\Controllers\Api\V1\FeaturedProductController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('featured-products', FeaturedProductController::class)->name('featured-products');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{slug}', [CategoryController::class, 'show'])
        ->where('slug', '[a-z0-9-]+')
        ->name('categories.show');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->whereNumber('product')
        ->name('products.show');

    Route::post('enquiries', [EnquiryController::class, 'store'])
        ->middleware('throttle:enquiries')
        ->name('enquiries.store');
});
