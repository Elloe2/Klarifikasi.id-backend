<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Membuat tabel `search_histories` untuk menyimpan
     * kata kunci dan metadata hasil teratas.
     */
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->unsignedInteger('results_count')->default(0);
            $table->string('top_title')->nullable();
            $table->string('top_link')->nullable();
            $table->string('top_thumbnail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel jika migrasi di-rollback.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_histories');
    }
};
