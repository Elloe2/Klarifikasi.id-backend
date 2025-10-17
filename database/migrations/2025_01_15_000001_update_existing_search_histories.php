<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Update existing search histories yang memiliki user_id = null
     * dengan user_id dari user pertama yang ada (untuk data testing)
     */
    public function up(): void
    {
        // Cek apakah ada data dengan user_id = null
        $nullUserHistories = DB::table('search_histories')
            ->whereNull('user_id')
            ->count();

        if ($nullUserHistories > 0) {
            // Ambil user pertama yang ada
            $firstUser = DB::table('users')->first();
            
            if ($firstUser) {
                // Update semua riwayat dengan user_id = null ke user pertama
                DB::table('search_histories')
                    ->whereNull('user_id')
                    ->update(['user_id' => $firstUser->id]);
                
                echo "Updated $nullUserHistories search histories with user_id: {$firstUser->id}\n";
            }
        }
    }

    /**
     * Rollback: Set user_id kembali ke null
     */
    public function down(): void
    {
        // Hanya untuk rollback jika diperlukan
        // DB::table('search_histories')->update(['user_id' => null]);
    }
};
