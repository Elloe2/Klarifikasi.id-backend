<?php

/**
 * Test script untuk cek API endpoint
 * Simulasi request dari frontend ke backend
 */

echo "=== TEST API ENDPOINT ===\n\n";

// Test endpoint health
echo "1. Test health endpoint...\n";
$healthUrl = 'https://klarifikasiid-backend-main-ki47jp.laravel.cloud/api/health';
$healthResponse = file_get_contents($healthUrl);
if ($healthResponse) {
    echo "   ✅ Health endpoint berfungsi\n";
    $healthData = json_decode($healthResponse, true);
    echo "   Status: " . ($healthData['status'] ?? 'unknown') . "\n";
    echo "   Database: " . ($healthData['database'] ?? 'unknown') . "\n";
} else {
    echo "   ❌ Health endpoint tidak berfungsi\n";
}
echo "\n";

// Test endpoint search (tanpa auth)
echo "2. Test search endpoint...\n";
$searchUrl = 'https://klarifikasiid-backend-main-ki47jp.laravel.cloud/api/search';
$searchData = json_encode(['query' => 'test query']);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $searchData
    ]
]);

$searchResponse = file_get_contents($searchUrl, false, $context);
if ($searchResponse) {
    echo "   ✅ Search endpoint berfungsi\n";
    $searchResult = json_decode($searchResponse, true);
    echo "   Query: " . ($searchResult['query'] ?? 'unknown') . "\n";
    echo "   Results count: " . count($searchResult['results'] ?? []) . "\n";
} else {
    echo "   ❌ Search endpoint tidak berfungsi\n";
}
echo "\n";

// Test endpoint history (dengan mock token)
echo "3. Test history endpoint...\n";
$historyUrl = 'https://klarifikasiid-backend-main-ki47jp.laravel.cloud/api/history';
$historyContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Authorization: Bearer mock-token'
    ]
]);

$historyResponse = file_get_contents($historyUrl, false, $historyContext);
if ($historyResponse) {
    echo "   ✅ History endpoint berfungsi\n";
    $historyResult = json_decode($historyResponse, true);
    echo "   Response: " . substr($historyResponse, 0, 100) . "...\n";
} else {
    echo "   ❌ History endpoint tidak berfungsi\n";
    echo "   Error: " . error_get_last()['message'] . "\n";
}
echo "\n";

echo "=== SELESAI ===\n";
