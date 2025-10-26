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

// Clear cache endpoint (untuk debugging)
Route::get('/clear-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        return response()->json([
            'status' => 'success',
            'message' => 'All caches cleared',
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
