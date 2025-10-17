<?php

/**
 * Test script untuk MySQL Laravel Cloud
 * Jalankan dengan: php test_mysql.php
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST MYSQL LARAVEL CLOUD ===\n\n";

try {
    // 1. Test koneksi database
    echo "1. Test koneksi database...\n";
    $connection = DB::connection();
    $pdo = $connection->getPdo();
    echo "   ✅ Koneksi berhasil\n";
    echo "   Database: " . $connection->getDatabaseName() . "\n";
    echo "   Driver: " . $connection->getDriverName() . "\n\n";

    // 2. Cek apakah tabel search_histories ada
    echo "2. Cek tabel search_histories...\n";
    $tables = DB::select("SHOW TABLES LIKE 'search_histories'");
    if (empty($tables)) {
        echo "   ❌ Tabel search_histories belum ada\n";
        echo "   Jalankan: php artisan migrate\n\n";
    } else {
        echo "   ✅ Tabel search_histories sudah ada\n\n";
    }

    // 3. Cek struktur tabel (jika ada)
    if (!empty($tables)) {
        echo "3. Cek struktur tabel search_histories...\n";
        $columns = DB::select("DESCRIBE search_histories");
        foreach ($columns as $column) {
            echo "   - {$column->Field}: {$column->Type}\n";
        }
        echo "\n";

        // 4. Cek data
        echo "4. Cek data di search_histories...\n";
        $total = DB::table('search_histories')->count();
        echo "   Total records: $total\n";

        if ($total > 0) {
            $withUserId = DB::table('search_histories')->whereNotNull('user_id')->count();
            $withoutUserId = DB::table('search_histories')->whereNull('user_id')->count();
            echo "   Dengan user_id: $withUserId\n";
            echo "   Tanpa user_id: $withoutUserId\n";
        }
    }

    // 5. Cek tabel users
    echo "\n5. Cek tabel users...\n";
    $userTables = DB::select("SHOW TABLES LIKE 'users'");
    if (empty($userTables)) {
        echo "   ❌ Tabel users belum ada\n";
    } else {
        echo "   ✅ Tabel users sudah ada\n";
        $userCount = DB::table('users')->count();
        echo "   Total users: $userCount\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   Pastikan database MySQL Laravel Cloud sudah dikonfigurasi dengan benar\n";
}

echo "\n=== SELESAI ===\n";
