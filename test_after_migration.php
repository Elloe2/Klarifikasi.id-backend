<?php

/**
 * Test script setelah migration
 * Test API endpoint dengan token yang valid
 */

echo "=== TEST SETELAH MIGRATION ===\n\n";

// Test endpoint history dengan token yang valid
echo "1. Test history endpoint dengan token...\n";

// Simulasi login untuk mendapatkan token
$loginUrl = 'https://klarifikasiid-backend-main-ki47jp.laravel.cloud/api/auth/login';
$loginData = json_encode([
    'email' => 'test@example.com', // Ganti dengan email yang valid
    'password' => 'password' // Ganti dengan password yang valid
]);

$loginContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $loginData
    ]
]);

$loginResponse = file_get_contents($loginUrl, false, $loginContext);
if ($loginResponse) {
    $loginResult = json_decode($loginResponse, true);
    if (isset($loginResult['token'])) {
        echo "   ✅ Login berhasil\n";
        $token = $loginResult['token'];
        
        // Test history dengan token
        $historyUrl = 'https://klarifikasiid-backend-main-ki47jp.laravel.cloud/api/history';
        $historyContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $token
            ]
        ]);
        
        $historyResponse = file_get_contents($historyUrl, false, $historyContext);
        if ($historyResponse) {
            echo "   ✅ History endpoint berfungsi dengan token\n";
            $historyResult = json_decode($historyResponse, true);
            echo "   Data count: " . count($historyResult['data'] ?? []) . "\n";
        } else {
            echo "   ❌ History endpoint masih error\n";
            echo "   Error: " . error_get_last()['message'] . "\n";
        }
    } else {
        echo "   ❌ Login gagal - tidak ada token\n";
        echo "   Response: " . $loginResponse . "\n";
    }
} else {
    echo "   ❌ Login endpoint tidak berfungsi\n";
    echo "   Error: " . error_get_last()['message'] . "\n";
}
echo "\n";

// Test endpoint search dengan token
echo "2. Test search endpoint dengan token...\n";
$searchUrl = 'https://klarifikasiid-backend-main-ki47jp.laravel.cloud/api/search';
$searchData = json_encode(['query' => 'test query setelah migration']);

$searchContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $searchData
    ]
]);

$searchResponse = file_get_contents($searchUrl, false, $searchContext);
if ($searchResponse) {
    echo "   ✅ Search endpoint berfungsi\n";
    $searchResult = json_decode($searchResponse, true);
    echo "   Query: " . ($searchResult['query'] ?? 'unknown') . "\n";
    echo "   Results count: " . count($searchResult['results'] ?? []) . "\n";
} else {
    echo "   ❌ Search endpoint tidak berfungsi\n";
}
echo "\n";

echo "=== SELESAI ===\n";
