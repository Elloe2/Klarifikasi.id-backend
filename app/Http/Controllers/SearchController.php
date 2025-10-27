<?php

namespace App\Http\Controllers;

use App\Services\GoogleSearchService;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Meng-handle permintaan pencarian Klarifikasi.id.
 * Menyambungkan frontend Flutter dengan service GoogleSearchService,
 * serta menyimpan riwayat ke database.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly GoogleSearchService $searchService,
        private readonly GeminiService $geminiService
    ) {
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
            $items = $this->searchService->search($validated['query']);
        } catch (RuntimeException $exception) {
            Log::warning('GoogleSearchService failed, returning fallback results.', [
                'error' => $exception->getMessage(),
            ]);

            $items = [];
        }

        // Analisis klaim dengan Gemini AI menggunakan hasil pencarian Google CSE
        $geminiAnalysis = $this->geminiService->analyzeClaim($validated['query'], $items);

        return response()->json([
            'query' => $validated['query'],
            'results' => $items,
            'gemini_analysis' => $geminiAnalysis,
            'fallback' => empty($items),
            'message' => empty($items)
                ? 'Google Custom Search tidak tersedia, menampilkan fallback AI.'
                : null,
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
