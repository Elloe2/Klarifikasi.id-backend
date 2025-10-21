<?php

echo "🔍 Gemini API Key Verification\n";
echo "==============================\n\n";

$apiKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';

echo "🔑 API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "📏 Key Length: " . strlen($apiKey) . " characters\n\n";

// Test multiple endpoints
$endpoints = [
    'gemini-pro' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
    'gemini-1.5-flash' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
    'gemini-1.5-pro' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent',
];

foreach ($endpoints as $model => $url) {
    echo "🧪 Testing $model...\n";
    
    $testUrl = $url . '?key=' . $apiKey;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Test']
                ]
            ]
        ],
        'generationConfig' => [
            'maxOutputTokens' => 10,
        ]
    ];
    
    $options = [
        'http' => [
            'header' => 'Content-Type: application/json',
            'method' => 'POST',
            'content' => json_encode($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($testUrl, false, $context);
    
    if ($result === FALSE) {
        echo "❌ $model: Failed\n";
    } else {
        $response = json_decode($result, true);
        if (isset($response['candidates'])) {
            echo "✅ $model: Working!\n";
        } else {
            echo "⚠️  $model: Response but no candidates\n";
            echo "   Response: " . substr($result, 0, 200) . "...\n";
        }
    }
    echo "\n";
}

echo "💡 Recommendations:\n";
echo "1. Check Google AI Studio: https://aistudio.google.com/\n";
echo "2. Verify API key is active and has quota\n";
echo "3. Enable Gemini API in Google Cloud Console\n";
echo "4. Check billing account is linked\n";
echo "5. Try generating a new API key\n";
