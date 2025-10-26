<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test route untuk verify routing works
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Web routes working',
        'timestamp' => now()
    ]);
});

// Test API route di web (temporary)
Route::get('/api-test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API test from web routes',
        'api_routes_loaded' => file_exists(base_path('routes/api.php'))
    ]);
});
