<?php

/**
 * Script untuk test riwayat pencarian
 * Jalankan dengan: php test_history.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== TEST RIWAYAT PENCARIAN ===\n\n";

try {
    // 1. Cek struktur tabel
    echo "1. Cek struktur tabel search_histories:\n";
    $columns = DB::select("DESCRIBE search_histories");
    foreach ($columns as $column) {
        echo "   - {$column->Field}: {$column->Type}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ERROR: {$e->getMessage()}\n\n";
}

try {
    // 2. Cek total data
    echo "2. Total data di search_histories:\n";
    $total = SearchHistory::count();
    echo "   Total: $total\n";
} catch (Exception $e) {
    echo "   ERROR: {$e->getMessage()}\n";
}

try {
    // 3. Cek data dengan user_id
    echo "3. Data dengan user_id:\n";
    $withUserId = SearchHistory::whereNotNull('user_id')->count();
    $withoutUserId = SearchHistory::whereNull('user_id')->count();
    echo "   Dengan user_id: $withUserId\n";
    echo "   Tanpa user_id: $withoutUserId\n\n";
} catch (Exception $e) {
    echo "   ERROR: {$e->getMessage()}\n\n";
}

try {
    // 4. Cek users
    echo "4. Total users:\n";
    $userCount = User::count();
    echo "   Total users: $userCount\n";

    if ($userCount > 0) {
        $firstUser = User::first();
        echo "   User pertama: {$firstUser->name} (ID: {$firstUser->id})\n";
        
        // 5. Cek riwayat user pertama
        echo "5. Riwayat user pertama:\n";
        $userHistories = SearchHistory::where('user_id', $firstUser->id)->count();
        echo "   Riwayat user {$firstUser->name}: $userHistories\n";
    }
} catch (Exception $e) {
    echo "   ERROR: {$e->getMessage()}\n";
}

echo "\n=== SELESAI ===\n";
