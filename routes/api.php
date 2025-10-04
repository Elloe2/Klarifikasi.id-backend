<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::middleware(['api'])->group(function () {
    Route::post('/search', [SearchController::class, 'search'])
        ->middleware('throttle:10,1');

    Route::get('/history', [SearchController::class, 'history'])
        ->middleware('throttle:30,1');
});
