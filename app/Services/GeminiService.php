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
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY')) ?? 'AIzaSyAvjaMWecq2PeHB8Vv4HBV8bBkKzzD9PmI';
        
        // Log API key untuk debugging (hanya sebagian)
        $maskedKey = substr($this->apiKey, 0, 10) . '...' . substr($this->apiKey, -4);
        Log::info('GeminiService initialized with API Key: ' . $maskedKey);
        
        // Validasi API key tanpa throw exception
        if (empty($this->apiKey) || strlen($this->apiKey) < 20) {
            Log::error('Invalid or missing Gemini API Key: ' . $this->apiKey);
        }
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
            Log::info('Sending request to Gemini API...');
            Log::info('Claim: ' . $claim);
            Log::info('Search Results Count: ' . count($searchResults));
            
            $prompt = $this->buildPrompt($claim, $searchResults);
            Log::info('Prompt built, length: ' . strlen($prompt));
            
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
                        'temperature' => 0.1,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 1024,
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ]
                    ]
                ]);

            Log::info('Gemini API Response Status: ' . $response->status());
            Log::info('Gemini API Response Body (first 500 chars): ' . substr($response->body(), 0, 500));
            
            if ($response->successful()) {
                $data = $response->json();
                $text = data_get($data, 'candidates.0.content.parts.0.text');

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
                
                // Return fallback dengan informasi dari Google CSE
                return $this->getFallbackWithSearchData($claim, $searchResults);
            }

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            Log::error('Gemini Service Exception Trace: ' . $e->getTraceAsString());
            return $this->getFallbackWithSearchData($claim, $searchResults);
        }
    }

    /**
     * Membangun prompt untuk Gemini AI dengan data pencarian Google CSE
     * SIMPLIFIED version - fokus pada explanation dan analysis saja
     */
    private function buildPrompt(string $claim, array $searchResults = []): string
    {
        $searchData = '';
        
        if (!empty($searchResults)) {
            $items = [];
            foreach ($searchResults as $index => $result) {
                $items[] = sprintf(
                    '%d. %s - %s',
                    $index + 1,
                    $result['title'] ?? 'Tidak ada judul',
                    $result['snippet'] ?? 'Tidak ada snippet'
                );
            }
            $searchData = "\n\nHasil Pencarian:\n" . implode("\n", $items);
        }

        return <<<PROMPT
Anda adalah pakar pemeriksa fakta. Analisis klaim berikut secara objektif dan ringkas dalam bahasa Indonesia.

KLAIM: "{$claim}"{$searchData}

Berikan analisis dalam format JSON dengan struktur berikut:
{
  "explanation": "Ringkasan singkat (1-2 kalimat) tentang klaim ini",
  "detailed_analysis": "Analisis mendalam berdasarkan data pencarian (2-3 paragraf)"
}

INSTRUKSI:
- Gunakan hanya informasi dari hasil pencarian di atas
- Jika data tidak cukup, nyatakan bahwa bukti tidak memadai
- Berikan penjelasan yang objektif dan berimbang
- Jangan tambahkan teks di luar JSON
- Pastikan JSON valid dan dapat di-parse

CONTOH FORMAT:
{
  "explanation": "Klaim ini benar berdasarkan bukti yang tersedia",
  "detailed_analysis": "Berdasarkan hasil pencarian, klaim ini didukung oleh beberapa sumber terpercaya yang menyebutkan..."
}
PROMPT;
    }

    /**
     * Parse response dari Gemini AI
     * Enhanced version dengan source analysis dan accuracy scoring
     */
    private function parseResponse(string $text, string $claim): array
    {
        try {
            // Log response untuk debugging
            Log::info('Gemini Raw Response: ' . $text);
            
            // Bersihkan response dari markdown formatting jika ada
            $cleanText = $this->cleanResponse($text);
            
            // Coba extract JSON dari response
            $jsonStart = strpos($cleanText, '{');
            $jsonEnd = strrpos($cleanText, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($cleanText, $jsonStart, $jsonEnd - $jsonStart + 1);
                Log::info('Extracted JSON: ' . $jsonString);
                
                $data = json_decode($jsonString, true);
                
                if ($data && isset($data['explanation'])) {
                    Log::info('Successfully parsed JSON response');
                    Log::info('Response data keys: ' . implode(', ', array_keys($data)));
                    
                    // Enhanced response dengan source analysis
                    $response = [
                        'success' => true,
                        'explanation' => (string) ($data['explanation'] ?? 'Tidak ada penjelasan tersedia'),
                        'detailed_analysis' => (string) ($data['detailed_analysis'] ?? $data['analysis'] ?? 'Tidak ada analisis tersedia'),
                        'claim' => (string) $claim,
                    ];
                    
                    // Add source analysis jika tersedia
                    if (!empty($data['source_analysis']) && is_array($data['source_analysis'])) {
                        Log::info('Found source_analysis in response');
                        $response['source_analysis'] = $data['source_analysis'];
                    }
                    
                    // Add statistics jika tersedia
                    if (!empty($data['statistics']) && is_array($data['statistics'])) {
                        Log::info('Found statistics in response');
                        $response['statistics'] = $data['statistics'];
                    }
                    
                    // Add accuracy score jika tersedia
                    if (!empty($data['accuracy_score']) && is_array($data['accuracy_score'])) {
                        Log::info('Found accuracy_score in response');
                        $response['accuracy_score'] = $data['accuracy_score'];
                    }
                    
                    // FALLBACK: Jika enhanced data tidak ada, generate dari explanation
                    if (empty($response['accuracy_score'])) {
                        Log::info('Generating accuracy_score from explanation (fallback)');
                        $response['accuracy_score'] = $this->generateAccuracyScoreFromExplanation(
                            $response['explanation'],
                            $claim
                        );
                    }
                    
                    if (empty($response['statistics'])) {
                        Log::info('Generating default statistics (fallback)');
                        $response['statistics'] = $this->generateDefaultStatistics();
                    }
                    
                    if (empty($response['source_analysis'])) {
                        Log::info('Setting empty source_analysis (fallback)');
                        $response['source_analysis'] = [];
                    }
                    
                    Log::info('Final response has accuracy_score: ' . (isset($response['accuracy_score']) ? 'YES' : 'NO'));
                    Log::info('Final response has statistics: ' . (isset($response['statistics']) ? 'YES' : 'NO'));
                    
                    return $response;
                } else {
                    Log::warning('JSON parsed but missing explanation field');
                }
            } else {
                Log::warning('No JSON found in response');
            }
            
            // Fallback jika JSON parsing gagal - coba parse manual
            return $this->parseTextResponse($cleanText, $claim);
            
        } catch (\Exception $e) {
            Log::error('Error parsing Gemini response: ' . $e->getMessage());
            return $this->getFallbackResponse($claim);
        }
    }

    /**
     * Bersihkan response dari markdown formatting dan JSON artifacts
     */
    private function cleanResponse(string $text): string
    {
        // Hapus markdown code blocks
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        
        // Hapus markdown formatting
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        
        // Hapus escaped quotes dan JSON artifacts
        $text = preg_replace('/\\"/', '"', $text);
        $text = preg_replace('/\\\\//', '\\', $text);
        
        // Hapus "Klaim {" pattern di awal
        $text = preg_replace('/^Klaim\s*\{/', '', $text);
        
        // Hapus pattern: {"explanation": "..." di awal (jika JSON terputus)
        $text = preg_replace('/^\{\s*"explanation"\s*:\s*"/', '', $text);
        
        // Hapus pattern: "Klaim \"..." di awal
        $text = preg_replace('/^"Klaim\s*\\"/', '', $text);
        
        // Hapus trailing quotes dan braces
        $text = preg_replace('/["}]+\s*$/', '', $text);
        
        // Hapus extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Parse response text jika JSON parsing gagal
     */
    private function parseTextResponse(string $text, string $claim): array
    {
        Log::info('Parsing text response (JSON parsing failed)');
        
        // Jika response tidak dalam format JSON, coba extract informasi manual
        $explanation = 'Tidak dapat menganalisis klaim ini dengan pasti.';
        $sources = '';
        $analysis = 'Tidak ada analisis tersedia';
        
        // Coba extract penjelasan dari response text
        if (!empty($text)) {
            // Ambil beberapa kalimat pertama sebagai explanation
            $sentences = preg_split('/[.!?]+/', $text);
            $explanation = trim($sentences[0] ?? $text);
            
            // Jika explanation terlalu panjang, potong
            if (strlen($explanation) > 200) {
                $explanation = substr($explanation, 0, 200) . '...';
            }
            
            // Gunakan seluruh response sebagai analysis
            $analysis = $text;
            if (strlen($analysis) > 500) {
                $analysis = substr($analysis, 0, 500) . '...';
            }
            
            $sources = '';
        }
        
        // Pastikan semua field adalah string
        $response = [
            'success' => true,
            'explanation' => (string) $explanation,
            'detailed_analysis' => (string) $analysis,
            'claim' => (string) $claim,
        ];
        
        // ALWAYS add enhanced data untuk consistency
        $response['accuracy_score'] = $this->generateAccuracyScoreFromExplanation(
            $explanation,
            $claim
        );
        $response['statistics'] = $this->generateDefaultStatistics();
        $response['source_analysis'] = [];
        
        Log::info('Text response parsed with enhanced data');
        
        return $response;
    }

    /**
     * Fallback response jika API gagal
     */
    private function getFallbackResponse(string $claim): array
    {
        $explanation = 'Tidak dapat menganalisis klaim ini saat ini. Silakan coba lagi nanti.';
        $analysis = 'Tidak ada analisis tersedia';
        
        $response = [
            'success' => true,  // Set true agar frontend bisa display dengan enhanced data
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
