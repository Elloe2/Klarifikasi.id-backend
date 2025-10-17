<?php

/**
 * Script untuk test Gemini API dengan API key yang diberikan
 * Jalankan dengan: php test_gemini_api.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeminiService;

echo "=== TEST GEMINI API ===\n\n";

try {
    // Set API key langsung
    $apiKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
    
    // Test dengan cURL langsung
    echo "1. Test dengan cURL langsung...\n";
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => 'Explain how AI works in a few words'
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-goog-api-key: ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "   HTTP Code: $httpCode\n";
    if ($error) {
        echo "   cURL Error: $error\n";
    } else {
        echo "   Response: " . substr($response, 0, 200) . "...\n";
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                echo "   ✅ Gemini API berfungsi!\n";
                echo "   AI Response: " . $data['candidates'][0]['content']['parts'][0]['text'] . "\n";
            }
        } else {
            echo "   ❌ Gemini API error\n";
        }
    }
    
    echo "\n2. Test dengan GeminiService...\n";
    
    // Set API key di config
    config(['services.gemini.api_key' => $apiKey]);
    
    $geminiService = new GeminiService();
    $claim = "Indonesia adalah negara terbesar di Asia Tenggara";
    
    echo "   Testing claim: \"$claim\"\n";
    $analysis = $geminiService->analyzeClaim($claim);
    
    echo "   Success: " . ($analysis['success'] ? 'true' : 'false') . "\n";
    echo "   Verdict: " . $analysis['verdict'] . "\n";
    echo "   Confidence: " . $analysis['confidence'] . "\n";
    echo "   Explanation: " . $analysis['explanation'] . "\n";
    
    if ($analysis['success']) {
        echo "   ✅ GeminiService berfungsi!\n";
    } else {
        echo "   ❌ GeminiService error: " . ($analysis['error'] ?? 'Unknown error') . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== SELESAI ===\n";
