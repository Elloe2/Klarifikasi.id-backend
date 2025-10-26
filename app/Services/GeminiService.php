<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk berkomunikasi dengan Google Gemini AI
 * Menggunakan HTTP client untuk mengakses Gemini API
 */
class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        // Try to get from env/config first
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
        
        // Fallback to hardcoded key if not configured
        if (empty($this->apiKey) || strlen($this->apiKey) < 20) {
            $this->apiKey = 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
            Log::warning('Using hardcoded Gemini API Key (env not configured)');
        }
        
        // Log API key untuk debugging (hanya sebagian)
        $maskedKey = substr($this->apiKey, 0, 10) . '...' . substr($this->apiKey, -4);
        Log::info('GeminiService initialized with API Key: ' . $maskedKey);
    }

    /**
     * Menganalisis klaim dengan menggunakan hasil pencarian Google CSE
     */
    public function analyzeClaim(string $claim, array $searchResults = []): array
    {
        // Log API key status
        $maskedKey = substr($this->apiKey, 0, 10) . '...' . substr($this->apiKey, -4);
        Log::info('GeminiService analyzeClaim called with API Key: ' . $maskedKey);
        
        // Check API key validity
        if (empty($this->apiKey) || strlen($this->apiKey) < 20) {
            Log::warning('Gemini API Key not configured properly, using fallback');
            return $this->getFallbackWithSearchData($claim, $searchResults);
        }
        
        try {
            Log::info('Gemini API Request: ' . $claim);
            
            $prompt = $this->buildPrompt($claim, $searchResults);
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $this->apiKey,
                ])
                ->post($this->baseUrl, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ]
                ]);

            if ($response->successful()) {
                try {
                    $data = $response->json();
                    $text = data_get($data, 'candidates.0.content.parts.0.text');
                    Log::info('Gemini response received successfully');
                } catch (\Exception $parseError) {
                    Log::error('JSON parse error: ' . $parseError->getMessage());
                    throw $parseError;
                }

                if (!is_string($text) || trim($text) === '') {
                    $blockReason = data_get($data, 'promptFeedback.blockReason');
                    $safetyRatings = data_get($data, 'promptFeedback.safetyRatings');
                    Log::error('Gemini API returned no analysable candidates.', [
                        'blockReason' => $blockReason,
                        'safetyRatings' => $safetyRatings,
                    ]);

                    // Use fallback dengan enhanced data
                    $fallback = $this->getFallbackWithSearchData($claim, $searchResults);
                    $message = $blockReason ? 'Analisis diblokir oleh Gemini AI.' : 'Gemini AI tidak mengembalikan analisis.';
                    $fallback['explanation'] = $message;
                    $fallback['error'] = $blockReason
                        ? 'Gemini AI memblokir analisis: ' . $blockReason
                        : 'Gemini AI tidak mengembalikan analisis.';
                    return $fallback;
                }

                Log::info('Gemini API Success - Response received');
                return $this->parseResponse((string) $text, $claim);
            } else {
                Log::error('Gemini API Error Status: ' . $response->status());
                Log::error('Gemini API Error Body: ' . $response->body());
                
                // Return error response dengan pesan jelas
                return [
                    'success' => true,
                    'explanation' => 'Gemini AI tidak terkoneksi',
                    'detailed_analysis' => 'Layanan Gemini AI sedang tidak tersedia. Status: ' . $response->status(),
                    'claim' => (string) $claim,
                    'error' => 'Gemini API Error: ' . $response->status(),
                    'accuracy_score' => $this->generateAccuracyScoreFromExplanation('Gemini tidak terkoneksi', $claim),
                    'statistics' => $this->generateDefaultStatistics(),
                    'source_analysis' => [],
                ];
            }

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            
            // Return error response dengan pesan jelas
            try {
                return [
                    'success' => true,
                    'explanation' => 'Gemini AI tidak terkoneksi',
                    'detailed_analysis' => 'Terjadi kesalahan saat menghubungi Gemini AI.',
                    'claim' => (string) $claim,
                    'error' => 'Gemini Connection Error',
                    'accuracy_score' => $this->generateAccuracyScoreFromExplanation('Gemini tidak terkoneksi', $claim),
                    'statistics' => $this->generateDefaultStatistics(),
                    'source_analysis' => [],
                ];
            } catch (\Exception $fallbackError) {
                Log::error('Fallback error: ' . $fallbackError->getMessage());
                return $this->getFallbackWithSearchData($claim, $searchResults);
            }
        }
    }

    /**
     * Membangun prompt untuk Gemini AI dengan data pencarian Google CSE
     * ULTRA SIMPLE - hanya minta plain text response
     */
    private function buildPrompt(string $claim, array $searchResults = []): string
    {
        return "Analisis klaim ini dengan singkat: {$claim}";
    }

    /**
     * Parse response dari Gemini AI
     * ULTRA SIMPLE - treat semua response sebagai plain text
     */
    private function parseResponse(string $text, string $claim): array
    {
        try {
            $cleanText = trim($text);
            
            // Use full text as both explanation and analysis
            $explanation = $cleanText;
            $analysis = $cleanText;
            
            // Limit explanation length
            if (strlen($explanation) > 200) {
                $explanation = substr($explanation, 0, 200) . '...';
            }
            
            $response = [
                'success' => true,
                'explanation' => $explanation ?: 'Analisis diterima dari Gemini AI',
                'detailed_analysis' => $analysis,
                'claim' => (string) $claim,
            ];
            
            // Always add enhanced data
            $response['accuracy_score'] = $this->generateAccuracyScoreFromExplanation(
                $cleanText,
                $claim
            );
            $response['statistics'] = $this->generateDefaultStatistics();
            $response['source_analysis'] = [];
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Error parsing response: ' . $e->getMessage());
            return $this->getFallbackResponse($claim);
        }
    }

    /**
     * Fallback response jika API gagal
     */
    private function getFallbackResponse(string $claim): array
    {
        $explanation = 'Gemini AI tidak terkoneksi';
        $analysis = 'Layanan Gemini AI sedang tidak tersedia. Silakan coba lagi nanti.';
        
        $response = [
            'success' => true,
            'explanation' => $explanation,
            'detailed_analysis' => $analysis,
            'claim' => (string) $claim,
            'error' => 'Gemini API tidak tersedia'
        ];
        
        // ALWAYS add enhanced data untuk consistency
        $response['accuracy_score'] = $this->generateAccuracyScoreFromExplanation(
            $explanation,
            $claim
        );
        $response['statistics'] = $this->generateDefaultStatistics();
        $response['source_analysis'] = [];
        
        return $response;
    }
    private function getFallbackWithSearchData(string $claim, array $searchResults = []): array
    {
        $explanation = 'Tidak dapat menganalisis klaim ini dengan AI saat ini.';
        $sources = '';
        $analysis = 'Tidak ada analisis tersedia';
        
        if (!empty($searchResults)) {
            $explanation = 'Berdasarkan hasil pencarian Google, klaim ini memerlukan verifikasi lebih lanjut.';
            $sources = '';
            
            $analysis = 'Analisis berdasarkan hasil pencarian Google:\n\n';
            foreach (array_slice($searchResults, 0, 3) as $index => $result) {
                $analysis .= ($index + 1) . '. ' . ($result['title'] ?? 'Tidak ada judul') . '\n';
                $analysis .= '   URL: ' . ($result['link'] ?? 'Tidak ada URL') . '\n';
                $analysis .= '   Snippet: ' . substr($result['snippet'] ?? 'Tidak ada snippet', 0, 100) . '...\n\n';
            }
            $analysis .= 'Silakan periksa sumber-sumber di atas untuk verifikasi lebih lanjut.';
        }
        
        $response = [
            'success' => true,
            'explanation' => $explanation,
            'detailed_analysis' => $analysis,
            'claim' => (string) $claim,
        ];
        
        // ALWAYS add enhanced data untuk consistency
        $response['accuracy_score'] = $this->generateAccuracyScoreFromExplanation(
            $explanation,
            $claim
        );
        $response['statistics'] = $this->generateDefaultStatistics();
        $response['source_analysis'] = [];
        
        return $response;
    }

    /**
     * Generate accuracy score dari explanation jika Gemini tidak mengirim structured data
     */
    private function generateAccuracyScoreFromExplanation(string $explanation, string $claim): array
    {
        // Analisis explanation untuk determine verdict
        $explanationLower = strtolower($explanation);
        
        // Heuristic: cek keywords untuk determine verdict
        $isFakta = (
            strpos($explanationLower, 'benar') !== false ||
            strpos($explanationLower, 'terbukti') !== false ||
            strpos($explanationLower, 'akurat') !== false ||
            strpos($explanationLower, 'tepat') !== false
        );
        
        $isHoax = (
            strpos($explanationLower, 'salah') !== false ||
            strpos($explanationLower, 'hoax') !== false ||
            strpos($explanationLower, 'tidak benar') !== false ||
            strpos($explanationLower, 'menyesatkan') !== false ||
            strpos($explanationLower, 'palsu') !== false
        );
        
        if ($isFakta && !$isHoax) {
            $verdict = 'FAKTA';
            $confidence = 75;
            $reasoning = 'Berdasarkan analisis, klaim ini didukung oleh bukti yang tersedia.';
            $recommendation = 'Klaim ini dapat dipercaya berdasarkan sumber-sumber yang ditemukan.';
        } elseif ($isHoax && !$isFakta) {
            $verdict = 'HOAX';
            $confidence = 70;
            $reasoning = 'Berdasarkan analisis, klaim ini tidak didukung oleh bukti yang valid.';
            $recommendation = 'Hati-hati dengan klaim ini, kemungkinan besar tidak akurat.';
        } else {
            $verdict = 'RAGU-RAGU';
            $confidence = 60;
            $reasoning = 'Berdasarkan analisis, klaim ini memiliki bukti yang beragam dan memerlukan verifikasi lebih lanjut.';
            $recommendation = 'Cari sumber tambahan untuk verifikasi lebih mendalam.';
        }
        
        return [
            'verdict' => $verdict,
            'confidence' => $confidence,
            'reasoning' => $reasoning,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Generate default statistics
     */
    private function generateDefaultStatistics(): array
    {
        return [
            'total_sources' => 0,
            'support_count' => 0,
            'oppose_count' => 0,
            'neutral_count' => 0,
        ];
    }
}
