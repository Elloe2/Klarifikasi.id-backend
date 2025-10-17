<?php

/**
 * Script untuk membuat data test di database
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SearchHistory;
use App\Models\User;

echo "=== CREATE TEST DATA ===\n\n";

try {
    // 1. Cek users
    echo "1. Cek users...\n";
    $userCount = User::count();
    echo "   Total users: $userCount\n";
    
    if ($userCount == 0) {
        echo "   ❌ Tidak ada users. Buat user test...\n";
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        echo "   ✅ User test dibuat: {$user->name} (ID: {$user->id})\n";
    } else {
        $firstUser = User::first();
        echo "   ✅ User pertama: {$firstUser->name} (ID: {$firstUser->id})\n";
    }
    
    // 2. Buat data test search_histories
    echo "\n2. Buat data test search_histories...\n";
    $firstUser = User::first();
    
    $testHistories = [
        [
            'user_id' => $firstUser->id,
            'query' => 'berita terbaru hari ini',
            'results_count' => 10,
            'top_title' => 'Berita Terbaru Hari Ini - CNN Indonesia',
            'top_link' => 'https://cnnindonesia.com/berita-terbaru',
            'top_thumbnail' => 'https://example.com/thumbnail1.jpg',
        ],
        [
            'user_id' => $firstUser->id,
            'query' => 'kabar politik hari ini',
            'results_count' => 8,
            'top_title' => 'Kabar Politik Terkini - Kompas',
            'top_link' => 'https://kompas.com/politik',
            'top_thumbnail' => 'https://example.com/thumbnail2.jpg',
        ],
        [
            'user_id' => $firstUser->id,
            'query' => 'hoax terbaru hari ini',
            'results_count' => 5,
            'top_title' => 'Hoax Terbaru yang Beredar - Detik',
            'top_link' => 'https://detik.com/hoax',
            'top_thumbnail' => 'https://example.com/thumbnail3.jpg',
        ],
    ];
    
    foreach ($testHistories as $historyData) {
        $history = SearchHistory::create($historyData);
        echo "   ✅ History dibuat: {$history->query} (ID: {$history->id})\n";
    }
    
    // 3. Verifikasi data
    echo "\n3. Verifikasi data...\n";
    $totalHistories = SearchHistory::count();
    $userHistories = SearchHistory::where('user_id', $firstUser->id)->count();
    
    echo "   Total histories: $totalHistories\n";
    echo "   Histories user {$firstUser->name}: $userHistories\n";
    
    if ($totalHistories > 0) {
        echo "\n🎉 Data test berhasil dibuat!\n";
        echo "   Sekarang bisa test API endpoint /api/history\n";
    } else {
        echo "\n❌ Data test gagal dibuat\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n=== SELESAI ===\n";
