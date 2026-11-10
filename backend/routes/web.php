<?php

use Illuminate\Support\Facades\Route;

// This app is an API only; the customer-facing site is the React app in /frontend.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/v1'),
]));
