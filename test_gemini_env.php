<?php

/**
 * Script untuk test environment variables Gemini
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST GEMINI ENVIRONMENT ===\n\n";

// Test environment variables
echo "1. Environment Variables:\n";
echo "   GEMINI_API_KEY: " . (env('GEMINI_API_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "   GOOGLE_CSE_KEY: " . (env('GOOGLE_CSE_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "   GOOGLE_CSE_CX: " . (env('GOOGLE_CSE_CX') ? 'SET' : 'NOT SET') . "\n";

echo "\n2. Config Values:\n";
echo "   services.gemini.api_key: " . (config('services.gemini.api_key') ? 'SET' : 'NOT SET') . "\n";
echo "   services.google_cse.key: " . (config('services.google_cse.key') ? 'SET' : 'NOT SET') . "\n";
echo "   services.google_cse.cx: " . (config('services.google_cse.cx') ? 'SET' : 'NOT SET') . "\n";

echo "\n3. Test GeminiService:\n";
try {
    $geminiService = new \App\Services\GeminiService();
    echo "   ✅ GeminiService instantiated successfully\n";
} catch (Exception $e) {
    echo "   ❌ GeminiService error: " . $e->getMessage() . "\n";
}

echo "\n4. Test SearchController:\n";
try {
    $searchService = new \App\Services\GoogleSearchService();
    $geminiService = new \App\Services\GeminiService();
    $controller = new \App\Http\Controllers\SearchController($searchService, $geminiService);
    echo "   ✅ SearchController instantiated successfully\n";
} catch (Exception $e) {
    echo "   ❌ SearchController error: " . $e->getMessage() . "\n";
}

echo "\n=== SELESAI ===\n";
