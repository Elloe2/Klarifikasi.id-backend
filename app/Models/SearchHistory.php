<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent untuk tabel `search_histories`.
 * Menyimpan rincian pencarian terbaru seperti query, jumlah hasil,
 * dan metadata hasil teratas. Setiap riwayat terikat dengan user tertentu.
 */
class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'results_count',
        'top_title',
        'top_link',
        'top_thumbnail',
    ];

    /**
     * Relasi dengan model User.
     * Setiap riwayat pencarian dimiliki oleh satu user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
