<?php

namespace App\Http\Controllers;

use App\Services\GoogleSearchService;
use App\Services\GeminiService;
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
        // Wrap everything in try-catch untuk prevent 500 errors
        try {
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
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 502);
            }

            // Analisis klaim dengan Gemini AI menggunakan hasil pencarian Google CSE
            try {
                $geminiAnalysis = $this->geminiService->analyzeClaim($validated['query'], $items);
            } catch (\Exception $exception) {
                // If Gemini fails, return results with fallback analysis
                $geminiAnalysis = [
                    'success' => true,
                    'explanation' => 'Gemini AI tidak tersedia saat ini',
                    'detailed_analysis' => 'Silakan periksa hasil pencarian di bawah untuk informasi lebih lanjut.',
                    'claim' => $validated['query'],
                    'error' => $exception->getMessage(),
                    'accuracy_score' => [
                        'verdict' => 'RAGU-RAGU',
                        'confidence' => 50,
                        'reasoning' => 'Analisis AI tidak tersedia',
                        'recommendation' => 'Periksa sumber-sumber di bawah secara manual'
                    ],
                    'statistics' => [
                        'total_sources' => 0,
                        'support_count' => 0,
                        'oppose_count' => 0,
                        'neutral_count' => 0
                    ],
                    'source_analysis' => []
                ];
            }

            return response()->json([
                'query' => $validated['query'],
                'results' => $items,
                'gemini_analysis' => $geminiAnalysis,
            ]);
            
        } catch (\Exception $e) {
            // Catch-all untuk any unexpected errors
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'query' => $request->input('query', ''),
                'results' => [],
                'gemini_analysis' => [
                    'success' => false,
                    'explanation' => 'Terjadi kesalahan pada server',
                    'detailed_analysis' => $e->getMessage(),
                    'claim' => $request->input('query', ''),
                    'error' => $e->getMessage(),
                ]
            ], 200); // Return 200 to prevent frontend error
        }
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
