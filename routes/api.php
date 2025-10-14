<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;

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

// Route pencarian tanpa autentikasi untuk testing
Route::post('/search', [SearchController::class, 'search'])
    ->middleware('throttle:10,1');

// Route untuk mendapatkan hasil pencarian berdasarkan query
Route::get('/search/{query}', [SearchController::class, 'searchByQuery'])
    ->middleware('throttle:10,1');

// Simple health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'database' => config('database.default'),
    ]);
});
