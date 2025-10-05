<?php

namespace App\Http\Controllers;

use App\Services\GoogleSearchService;
use App\Models\SearchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        SearchHistory::query()->create([
            'query' => $validated['query'],
            'results_count' => count($items),
            'top_title' => $firstItem['title'] ?? null,
            'top_link' => $firstItem['link'] ?? null,
            'top_thumbnail' => $firstItem['thumbnail'] ?? null,
        ]);

        return response()->json([
            'query' => $validated['query'],
            'results' => $items,
        ]);
    }

    /**
     * Mengembalikan riwayat pencarian terbaru dengan pagination sederhana.
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);

        $histories = SearchHistory::query()
            ->latest()
            ->paginate(min($perPage, 50));

        return response()->json($histories);
    }

    /**
     * Menghapus seluruh riwayat pencarian.
     */
    public function clear(): JsonResponse
    {
        SearchHistory::query()->delete();

        return response()->json([
            'message' => 'Riwayat pencarian berhasil dihapus.',
        ]);
    }
}
