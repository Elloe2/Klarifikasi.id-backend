<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::post('/search', [SearchController::class, 'search'])
        ->middleware('throttle:10,1');

    Route::get('/history', [SearchController::class, 'history'])
        ->middleware('throttle:30,1');

    Route::delete('/history', [SearchController::class, 'clear'])
        ->middleware('throttle:10,1');
});
