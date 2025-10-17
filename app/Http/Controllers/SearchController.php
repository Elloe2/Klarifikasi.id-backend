<?php

namespace App\Http\Controllers;

use App\Services\GoogleSearchService;
use App\Models\SearchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Meng-handle permintaan pencarian Klarifikasi.id.
 * Menyambungkan frontend Flutter dengan service GoogleSearchService,
 * serta menyimpan riwayat ke database.
 */
class SearchController extends Controller
{
    public function __construct(private readonly GoogleSearchService $service)
    {
    }

    /**
     * Menerima query dari frontend, memvalidasi, memanggil Google, dan
     * menyimpan riwayat pencarian.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => ['required', 'string', 'min:3', 'max:255'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Invalid query.',
                'errors' => $exception->errors(),
            ], 422);
        }

        try {
            $items = $this->service->search($validated['query']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        $firstItem = $items[0] ?? null;

        // Simpan riwayat pencarian dengan user_id jika user sudah login
        $userId = Auth::id();
        if ($userId) {
            // Hanya simpan riwayat jika user sudah login
            SearchHistory::query()->create([
                'user_id' => $userId, // Tambahkan user_id untuk memisahkan riwayat antar user
                'query' => $validated['query'],
                'results_count' => count($items),
                'top_title' => $firstItem['title'] ?? null,
                'top_link' => $firstItem['link'] ?? null,
                'top_thumbnail' => $firstItem['thumbnail'] ?? null,
            ]);
        }

        return response()->json([
            'query' => $validated['query'],
            'results' => $items,
        ]);
    }

    /**
     * Mengembalikan riwayat pencarian terbaru dengan pagination sederhana.
     * Hanya menampilkan riwayat dari user yang sedang login.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $userId = Auth::id();

        $histories = SearchHistory::query()
            ->where('user_id', $userId) // Filter berdasarkan user yang sedang login
            ->latest()
            ->paginate(min($perPage, 50));

        return response()->json($histories);
    }

    /**
     * Menghapus seluruh riwayat pencarian dari user yang sedang login.
     */
    public function clear(): JsonResponse
    {
        $userId = Auth::id();
        
        SearchHistory::query()
            ->where('user_id', $userId) // Hanya hapus riwayat dari user yang sedang login
            ->delete();

        return response()->json([
            'message' => 'Riwayat pencarian berhasil dihapus.',
        ]);
    }

    /**
     * Mencari berdasarkan query dari URL parameter
     */
    public function searchByQuery(Request $request, string $query): JsonResponse
    {
        // Buat request baru dengan query dari URL
        $searchRequest = Request::create('/api/search', 'POST', ['query' => $query]);

        // Copy headers yang diperlukan
        $searchRequest->headers->set('Content-Type', 'application/json');
        $searchRequest->headers->set('Accept', 'application/json');

        // Panggil method search yang sudah ada
        return $this->search($searchRequest);
    }
}
