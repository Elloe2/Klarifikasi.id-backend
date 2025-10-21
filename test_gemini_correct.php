<?php

echo "🧪 Testing Gemini API dengan Format yang Benar\n";
echo "==============================================\n\n";

$apiKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
$baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

echo "🔑 API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "🌐 URL: $baseUrl\n\n";

try {
    echo "📡 Sending test request to Gemini API...\n";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Explain how AI works in a few words']
                ]
            ]
        ]
    ];
    
    $options = [
        'http' => [
            'header' => [
                'Content-Type: application/json',
                'X-goog-api-key: ' . $apiKey
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
        echo "💡 Check your internet connection\n";
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
