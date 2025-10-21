<?php

echo "🧪 Testing Gemini API Connection (Simple)\n";
echo "==========================================\n\n";

// Test API Key
$apiKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
$baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

echo "🔑 API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "🌐 URL: $baseUrl\n\n";

try {
    echo "📡 Sending test request to Gemini API...\n";
    
    $data = [
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
    ];
    
    $options = [
        'http' => [
            'header' => [
                'Content-Type: application/json'
            ],
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($baseUrl, false, $context);
    
    if ($result === FALSE) {
        echo "❌ FAILED to connect to API!\n";
        echo "💡 Check your internet connection and API key\n";
    } else {
        $response = json_decode($result, true);
        
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            echo "✅ SUCCESS!\n";
            echo "📝 Response: " . $text . "\n";
            echo "\n🎉 Gemini API is working correctly!\n";
        } else {
            echo "❌ FAILED!\n";
            echo "📄 Response: " . $result . "\n";
            echo "\n💡 API returned unexpected format\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ EXCEPTION!\n";
    echo "📄 Error: " . $e->getMessage() . "\n";
}
