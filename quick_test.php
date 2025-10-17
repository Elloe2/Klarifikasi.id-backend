<?php

/**
 * Quick test script untuk cek riwayat pencarian
 * Jalankan dengan: php quick_test.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== QUICK TEST RIWAYAT ===\n\n";

try {
    // Cek apakah kolom user_id ada
    $columns = DB::select("SHOW COLUMNS FROM search_histories LIKE 'user_id'");
    if (empty($columns)) {
        echo "❌ Kolom 'user_id' belum ada di tabel search_histories\n";
        echo "   Jalankan: php artisan migrate\n\n";
    } else {
        echo "✅ Kolom 'user_id' sudah ada\n\n";
    }

    // Cek total data
    $total = SearchHistory::count();
    echo "📊 Total riwayat: $total\n";

    // Cek data dengan/tanpa user_id
    $withUserId = SearchHistory::whereNotNull('user_id')->count();
    $withoutUserId = SearchHistory::whereNull('user_id')->count();
    echo "👤 Dengan user_id: $withUserId\n";
    echo "❓ Tanpa user_id: $withoutUserId\n\n";

    // Cek users
    $userCount = User::count();
    echo "👥 Total users: $userCount\n";

    if ($userCount > 0) {
        $firstUser = User::first();
        echo "🔍 User pertama: {$firstUser->name} (ID: {$firstUser->id})\n";
        
        $userHistories = SearchHistory::where('user_id', $firstUser->id)->count();
        echo "📝 Riwayat user ini: $userHistories\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n=== SELESAI ===\n";
