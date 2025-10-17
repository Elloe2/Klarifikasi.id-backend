<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;

// Simple health check endpoint - harus di atas untuk priority
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Klarifikasi.id Backend API',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'database' => config('database.default'),
        'version' => '1.0.0'
    ]);
});

// Test Google CSE connection
Route::get('/test-google-cse', function () {
    try {
        $key = config('services.google_cse.key');
        $cx = config('services.google_cse.cx');

        return response()->json([
            'google_cse_configured' => !empty($key) && !empty($cx),
            'key_length' => strlen($key ?? ''),
            'cx_length' => strlen($cx ?? ''),
            'ssl_verify' => config('services.google_cse.verify_ssl', false)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// Route pencarian dengan autentikasi opsional
Route::post('/search', [SearchController::class, 'search'])
    ->middleware('throttle:10,1');

// Route untuk mendapatkan hasil pencarian berdasarkan query
Route::get('/search/{query}', [SearchController::class, 'searchByQuery'])
    ->middleware('throttle:10,1');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Route yang memerlukan autentikasi
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::get('/history', [SearchController::class, 'history'])
        ->middleware('throttle:30,1');

    Route::delete('/history', [SearchController::class, 'clear'])
        ->middleware('throttle:10,1');
});
