<?php

require_once 'vendor/autoload.php';

use App\Services\GoogleSearchService;
use App\Services\GeminiService;

echo "🧪 Testing Gemini AI dengan Google CSE Integration\n";
echo "================================================\n\n";

// Test query
$testQuery = "Vaksin COVID-19 menyebabkan autisme";

echo "🔍 Test Query: \"$testQuery\"\n\n";

try {
    // Initialize services
    $searchService = new GoogleSearchService();
    $geminiService = new GeminiService();
    
    echo "1️⃣ Mencari dengan Google CSE...\n";
    $searchResults = $searchService->search($testQuery);
    
    echo "✅ Ditemukan " . count($searchResults) . " hasil pencarian\n\n";
    
    // Tampilkan beberapa hasil pencarian
    echo "📋 Hasil Pencarian Google CSE:\n";
    foreach (array_slice($searchResults, 0, 3) as $index => $result) {
        echo ($index + 1) . ". " . ($result['title'] ?? 'Tidak ada judul') . "\n";
        echo "   URL: " . ($result['link'] ?? 'Tidak ada URL') . "\n";
        echo "   Domain: " . ($result['displayLink'] ?? 'Tidak ada domain') . "\n";
        echo "   Snippet: " . substr($result['snippet'] ?? 'Tidak ada snippet', 0, 100) . "...\n\n";
    }
    
    echo "2️⃣ Menganalisis dengan Gemini AI...\n";
    $geminiAnalysis = $geminiService->analyzeClaim($testQuery, $searchResults);
    
    echo "✅ Analisis Gemini selesai\n\n";
    
    // Tampilkan hasil analisis
    echo "🤖 Hasil Analisis Gemini AI:\n";
    echo "========================\n";
    echo "Success: " . ($geminiAnalysis['success'] ? '✅ Ya' : '❌ Tidak') . "\n";
    echo "Claim: " . $geminiAnalysis['claim'] . "\n\n";
    
    echo "📝 Explanation:\n";
    echo $geminiAnalysis['explanation'] . "\n\n";
    
    if (isset($geminiAnalysis['analysis']) && !empty($geminiAnalysis['analysis'])) {
        echo "🔍 Analysis:\n";
        echo $geminiAnalysis['analysis'] . "\n\n";
    }
    
    echo "📚 Sources:\n";
    echo $geminiAnalysis['sources'] . "\n\n";
    
    if (isset($geminiAnalysis['error'])) {
        echo "❌ Error: " . $geminiAnalysis['error'] . "\n";
    }
    
    echo "\n🎉 Test berhasil! Gemini AI sekarang bisa menganalisis hasil pencarian Google CSE!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
