<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;

Route::middleware(['api'])
    ->post('/search', [SearchController::class, 'search']);
