<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Middleware\VerifyCsrfToken;

Route::get('/', function () {
    return view('welcome');
});

Route::options('/', function () {
    return response()->json([], 204);
})->middleware('throttle:10,1');

Route::post('/', [SearchController::class, 'search'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware('throttle:10,1');
