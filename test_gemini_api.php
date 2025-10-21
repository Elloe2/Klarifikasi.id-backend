<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

echo "🧪 Testing Gemini API Connection\n";
echo "================================\n\n";

// Test API Key
$apiKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
$baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

echo "🔑 API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "🌐 URL: $baseUrl\n\n";

try {
    echo "📡 Sending test request to Gemini API...\n";
    
    $response = Http::timeout(30)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])
        ->post($baseUrl, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Hello, can you respond with "API working" in Indonesian?']
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 100,
            ]
        ]);

    echo "📊 Response Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text found';
        
        echo "✅ SUCCESS!\n";
        echo "📝 Response: " . $text . "\n";
        echo "\n🎉 Gemini API is working correctly!\n";
    } else {
        echo "❌ FAILED!\n";
        echo "📄 Error Body: " . $response->body() . "\n";
        echo "\n💡 Possible issues:\n";
        echo "- API Key is invalid or expired\n";
        echo "- API quota exceeded\n";
        echo "- Network connectivity issues\n";
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION!\n";
    echo "📄 Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
