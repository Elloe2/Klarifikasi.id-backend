<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambahkan kolom user_id ke tabel search_histories
     * untuk memisahkan riwayat pencarian antar user.
     */
    public function up(): void
    {
        Schema::table('search_histories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('user_id'); // Index untuk performa query yang lebih baik
        });
    }

    /**
     * Menghapus kolom user_id jika migrasi di-rollback.
     */
    public function down(): void
    {
        Schema::table('search_histories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
