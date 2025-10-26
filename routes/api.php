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

// Test endpoint untuk debug
Route::get('/test-search', function () {
    try {
        return response()->json([
            'status' => 'ok',
            'message' => 'Search endpoint accessible',
            'timestamp' => now(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Simple search test tanpa services
Route::post('/search-simple', function (Illuminate\Http\Request $request) {
    return response()->json([
        'query' => $request->input('query'),
        'results' => [],
        'gemini_analysis' => [
            'success' => true,
            'explanation' => 'Test response',
            'detailed_analysis' => 'This is a test',
            'claim' => $request->input('query'),
            'accuracy_score' => [
                'verdict' => 'RAGU-RAGU',
                'confidence' => 50,
                'reasoning' => 'Test',
                'recommendation' => 'Test'
            ],
            'statistics' => [
                'total_sources' => 0,
                'support_count' => 0,
                'oppose_count' => 0,
                'neutral_count' => 0
            ],
            'source_analysis' => []
        ]
    ]);
});

// Route pencarian dengan autentikasi opsional - TEMPORARILY DISABLED FOR DEBUG
// Route::post('/search', [SearchController::class, 'search'])
//     ->middleware('throttle:10,1');

// TEMPORARY: Simple search endpoint untuk bypass controller - WITH TEST DATA
Route::post('/search', function (Illuminate\Http\Request $request) {
    \Log::info('Search endpoint called', ['query' => $request->input('query')]);
    
    return response()->json([
        'query' => $request->input('query', ''),
        'results' => [
            [
                'title' => 'Test Result 1',
                'snippet' => 'This is a test result snippet',
                'link' => 'https://example.com/1',
                'displayLink' => 'example.com',
                'formattedUrl' => 'https://example.com/1',
            ],
            [
                'title' => 'Test Result 2',
                'snippet' => 'Another test result',
                'link' => 'https://example.com/2',
                'displayLink' => 'example.com',
                'formattedUrl' => 'https://example.com/2',
            ]
        ],
        'gemini_analysis' => [
            'success' => true,
            'explanation' => 'TEST: Backend berfungsi dengan baik!',
            'detailed_analysis' => 'Ini adalah response test dari backend.',
            'claim' => $request->input('query', ''),
            'accuracy_score' => [
                'verdict' => 'FAKTA',
                'confidence' => 85,
                'reasoning' => 'Test response',
                'recommendation' => 'Backend working'
            ],
            'statistics' => [
                'total_sources' => 2,
                'support_count' => 2,
                'oppose_count' => 0,
                'neutral_count' => 0
            ],
            'source_analysis' => []
        ]
    ]);
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
