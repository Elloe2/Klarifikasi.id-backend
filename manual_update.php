<?php

/**
 * Manual update script untuk menambahkan kolom user_id
 * Jalankan di Laravel Cloud atau server dengan PostgreSQL driver
 */

require_once 'vendor/autoload.php';

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MANUAL UPDATE DATABASE ===\n\n";

try {
    // 1. Cek apakah kolom user_id sudah ada
    echo "1. Cek apakah kolom user_id sudah ada...\n";
    $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'search_histories' AND column_name = 'user_id'");
    
    if (empty($columns)) {
        echo "   Kolom user_id belum ada, menambahkan...\n";
        
        // 2. Tambahkan kolom user_id
        DB::statement("ALTER TABLE search_histories ADD COLUMN user_id BIGINT UNSIGNED NULL");
        echo "   ✅ Kolom user_id berhasil ditambahkan\n";
        
        // 3. Tambahkan foreign key constraint
        DB::statement("ALTER TABLE search_histories ADD CONSTRAINT search_histories_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        echo "   ✅ Foreign key constraint berhasil ditambahkan\n";
        
        // 4. Tambahkan index
        DB::statement("CREATE INDEX search_histories_user_id_index ON search_histories (user_id)");
        echo "   ✅ Index berhasil ditambahkan\n";
        
    } else {
        echo "   ✅ Kolom user_id sudah ada\n";
    }
    
    // 5. Update data lama dengan user_id
    echo "\n2. Update data lama dengan user_id...\n";
    $nullUserHistories = DB::table('search_histories')->whereNull('user_id')->count();
    
    if ($nullUserHistories > 0) {
        // Ambil user pertama
        $firstUser = DB::table('users')->first();
        
        if ($firstUser) {
            DB::table('search_histories')
                ->whereNull('user_id')
                ->update(['user_id' => $firstUser->id]);
            
            echo "   ✅ Updated $nullUserHistories records with user_id: {$firstUser->id}\n";
        } else {
            echo "   ⚠️  Tidak ada user yang ditemukan\n";
        }
    } else {
        echo "   ✅ Semua data sudah memiliki user_id\n";
    }
    
    // 6. Verifikasi hasil
    echo "\n3. Verifikasi hasil...\n";
    $total = DB::table('search_histories')->count();
    $withUserId = DB::table('search_histories')->whereNotNull('user_id')->count();
    $withoutUserId = DB::table('search_histories')->whereNull('user_id')->count();
    
    echo "   Total records: $total\n";
    echo "   Dengan user_id: $withUserId\n";
    echo "   Tanpa user_id: $withoutUserId\n";
    
    if ($withoutUserId == 0) {
        echo "\n🎉 SUKSES! Semua riwayat sudah memiliki user_id\n";
    } else {
        echo "\n⚠️  Masih ada $withoutUserId records tanpa user_id\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
}

echo "\n=== SELESAI ===\n";
