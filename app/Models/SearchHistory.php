<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Eloquent untuk tabel `search_histories`.
 * Menyimpan rincian pencarian terbaru seperti query, jumlah hasil,
 * dan metadata hasil teratas.
 */
class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'results_count',
        'top_title',
        'top_link',
        'top_thumbnail',
    ];
}
