<?php

/**
 * Test script untuk cek apakah migration berhasil
 * Jalankan di Laravel Cloud environment
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST MIGRATION USER_ID ===\n\n";

try {
    // 1. Cek apakah kolom user_id sudah ada
    echo "1. Cek apakah kolom user_id sudah ada...\n";
    $columns = DB::select("SHOW COLUMNS FROM search_histories LIKE 'user_id'");
    
    if (empty($columns)) {
        echo "   ❌ Kolom user_id belum ada\n";
        echo "   Jalankan migration atau SQL manual\n\n";
    } else {
        echo "   ✅ Kolom user_id sudah ada\n";
        echo "   Type: {$columns[0]->Type}\n";
        echo "   Null: {$columns[0]->Null}\n\n";
    }

    // 2. Cek foreign key constraint
    echo "2. Cek foreign key constraint...\n";
    $constraints = DB::select("
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_NAME = 'search_histories' 
        AND COLUMN_NAME = 'user_id' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if (empty($constraints)) {
        echo "   ❌ Foreign key constraint belum ada\n";
    } else {
        echo "   ✅ Foreign key constraint sudah ada\n";
        echo "   Constraint: {$constraints[0]->CONSTRAINT_NAME}\n";
        echo "   References: {$constraints[0]->REFERENCED_TABLE_NAME}.{$constraints[0]->REFERENCED_COLUMN_NAME}\n";
    }
    echo "\n";

    // 3. Cek index
    echo "3. Cek index...\n";
    $indexes = DB::select("SHOW INDEX FROM search_histories WHERE Column_name = 'user_id'");
    
    if (empty($indexes)) {
        echo "   ❌ Index belum ada\n";
    } else {
        echo "   ✅ Index sudah ada\n";
        echo "   Index: {$indexes[0]->Key_name}\n";
    }
    echo "\n";

    // 4. Cek data
    echo "4. Cek data...\n";
    $total = DB::table('search_histories')->count();
    $withUserId = DB::table('search_histories')->whereNotNull('user_id')->count();
    $withoutUserId = DB::table('search_histories')->whereNull('user_id')->count();
    
    echo "   Total records: $total\n";
    echo "   Dengan user_id: $withUserId\n";
    echo "   Tanpa user_id: $withoutUserId\n\n";

    // 5. Cek users
    echo "5. Cek users...\n";
    $userCount = DB::table('users')->count();
    echo "   Total users: $userCount\n";
    
    if ($userCount > 0) {
        $firstUser = DB::table('users')->first();
        echo "   User pertama: {$firstUser->name} (ID: {$firstUser->id})\n";
        
        $userHistories = DB::table('search_histories')->where('user_id', $firstUser->id)->count();
        echo "   Riwayat user ini: $userHistories\n";
    }

    // 6. Kesimpulan
    echo "\n6. Kesimpulan:\n";
    if (!empty($columns) && !empty($constraints) && !empty($indexes)) {
        echo "   🎉 MIGRATION BERHASIL! Semua komponen sudah ada\n";
        if ($withoutUserId == 0) {
            echo "   ✅ Semua data sudah memiliki user_id\n";
        } else {
            echo "   ⚠️  Masih ada $withoutUserId records tanpa user_id\n";
            echo "   Jalankan: UPDATE search_histories SET user_id = 1 WHERE user_id IS NULL;\n";
        }
    } else {
        echo "   ❌ MIGRATION BELUM LENGKAP\n";
        echo "   Jalankan migration atau SQL manual\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n=== SELESAI ===\n";
