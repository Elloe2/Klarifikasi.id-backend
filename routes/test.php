<?php

use Illuminate\Support\Facades\Route;
use App\Services\GoogleSearchService;
use App\Services\GeminiService;

Route::get('/test-search', function () {
    try {
        $searchService = new GoogleSearchService();
        $results = $searchService->search('test');
        
        return response()->json([
            'success' => true,
            'results_count' => count($results),
            'results' => $results
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/test-gemini', function () {
    try {
        $geminiService = new GeminiService();
        $analysis = $geminiService->analyzeClaim('test claim', []);
        
        return response()->json([
            'success' => true,
            'analysis' => $analysis
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
