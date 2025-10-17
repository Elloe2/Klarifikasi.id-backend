<?php

namespace App\Http\Controllers;

use App\Services\GoogleSearchService;
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


        return response()->json([
            'query' => $validated['query'],
            'results' => $items,
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
