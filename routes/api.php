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
        'version' => '2.0.0'
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

// Test Gemini API connection
Route::get('/test-gemini', function () {
    try {
        $key = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        return response()->json([
            'gemini_configured' => !empty($key),
            'key_length' => strlen($key ?? ''),
            'key_preview' => substr($key ?? '', 0, 10) . '...' . substr($key ?? '', -4),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// Test Gemini API dengan simple request
Route::post('/test-gemini-request', function () {
    try {
        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        
        if (empty($apiKey)) {
            return response()->json(['error' => 'API Key not configured'], 400);
        }
        
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])
            ->post($baseUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Halo, siapa nama Anda?']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 100,
                ],
            ]);
        
        return response()->json([
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->json(),
            'raw_body' => substr($response->body(), 0, 500),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
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

});
