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

// MINIMAL test endpoint
Route::any('/ping', function () {
    return ['pong' => true, 'time' => time()];
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

// Test Gemini API configuration
Route::get('/test-gemini', function () {
    try {
        $key = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $hardcodedKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';

        return response()->json([
            'gemini_configured' => !empty($key),
            'key_from_env' => !empty($key),
            'key_length' => strlen($key ?? ''),
            'key_preview' => substr($key ?? '', 0, 10) . '...' . substr($key ?? '', -4),
            'hardcoded_key_available' => !empty($hardcodedKey),
            'hardcoded_preview' => substr($hardcodedKey, 0, 10) . '...' . substr($hardcodedKey, -4),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});

// Test Gemini API dengan simple request (GET untuk mudah di-test dari browser)
Route::get('/test-gemini-request', function () {
    try {
        $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        
        if (empty($apiKey)) {
            return response()->json(['error' => 'API Key not configured'], 400);
        }
        
        \Illuminate\Support\Facades\Log::info('Testing Gemini API with key: ' . substr($apiKey, 0, 10) . '...');
        
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $apiKey,
            ])
            ->post($baseUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Halo, siapa nama Anda? Jawab dalam 1 kalimat saja.']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 100,
                ],
            ]);
        
        $result = [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'headers' => $response->headers(),
        ];
        
        if ($response->successful()) {
            $result['response'] = $response->json();
            $result['text'] = data_get($response->json(), 'candidates.0.content.parts.0.text');
        } else {
            $result['error_body'] = $response->json();
            $result['raw_body'] = substr($response->body(), 0, 1000);
        }
        
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// DIAGNOSTIC: Test if route is accessible
Route::post('/search', function (Illuminate\Http\Request $request) {
    try {
        \Log::info('Search route hit', ['query' => $request->input('query')]);
        
        // Try to instantiate services
        $googleService = app(\App\Services\GoogleSearchService::class);
        $geminiService = app(\App\Services\GeminiService::class);
        
        \Log::info('Services instantiated successfully');
        
        // Return simple response
        return response()->json([
            'status' => 'ok',
            'message' => 'Route accessible, services loaded',
            'query' => $request->input('query'),
            'results' => [],
            'gemini_analysis' => [
                'success' => true,
                'explanation' => 'Diagnostic: Backend berfungsi',
                'detailed_analysis' => 'Services dapat di-load',
                'claim' => $request->input('query', ''),
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Search route error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

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
