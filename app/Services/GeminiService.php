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
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $this->apiKey,
                ])
                ->post($this->baseUrl, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $this->buildPrompt($claim, $searchResults)]
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

                    $fallback = $this->getFallbackWithSearchData($claim, $searchResults);
                    $message = $blockReason ? 'Analisis diblokir oleh Gemini AI.' : 'Gemini AI tidak mengembalikan analisis.';
                    $fallback['success'] = false;
                    $fallback['explanation'] = $message;
                    $fallback['sources'] = 'Gemini AI';
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
     * Enhanced version dengan source analysis dan accuracy scoring
     */
    private function buildPrompt(string $claim, array $searchResults = []): string
    {
        $searchData = '';
        
        if (!empty($searchResults)) {
            $items = [];
            foreach ($searchResults as $index => $result) {
                $items[] = sprintf(
                    '%d. situs="%s" judul="%s" url="%s" ringkasan="%s"',
                    $index + 1,
                    $result['displayLink'] ?? 'Tidak ada domain',
                    $result['title'] ?? 'Tidak ada judul',
                    $result['link'] ?? 'Tidak ada URL',
                    $result['snippet'] ?? 'Tidak ada snippet'
                );
            }
            $searchData = "\n\nDATA_PENDUKUNG:\n" . implode("\n", $items);
        }

        $jsonTemplate = json_encode([
            'explanation' => 'Ringkasan singkat tentang klaim',
            'detailed_analysis' => 'Analisis mendalam berdasarkan data',
            'source_analysis' => [
                [
                    'index' => 1,
                    'stance' => 'SUPPORT|OPPOSE|NEUTRAL',
                    'reasoning' => 'Penjelasan singkat mengapa sumber ini mendukung/menolak/netral',
                    'quote' => 'Kutipan relevan dari sumber (jika ada)'
                ]
            ],
            'statistics' => [
                'total_sources' => count($searchResults),
                'support_count' => 0,
                'oppose_count' => 0,
                'neutral_count' => 0
            ],
            'accuracy_score' => [
                'verdict' => 'FAKTA|RAGU-RAGU|HOAX',
                'confidence' => 0,
                'reasoning' => 'Penjelasan mengapa diberikan verdict ini',
                'recommendation' => 'Rekomendasi untuk user'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Anda adalah pakar pemeriksa fakta profesional. Analisis klaim berikut secara mendalam dan objektif dalam bahasa Indonesia.

KLAIM: "{$claim}"{$searchData}

INSTRUKSI ANALISIS:

1. ANALISIS SETIAP SUMBER:
   Untuk setiap sumber, tentukan sikapnya terhadap klaim:
   - SUPPORT: Sumber memberikan bukti kuat yang memverifikasi klaim
   - OPPOSE: Sumber memberikan bukti yang bertentangan atau menolak klaim
   - NEUTRAL: Sumber membahas topik terkait tapi tidak eksplisit mendukung/menolak

2. KUANTIFIKASI DUKUNGAN:
   Hitung jumlah sumber per kategori dan berikan persentase

3. PENILAIAN AKURASI:
   - FAKTA: ≥70% Mendukung, <20% Menyangkal, Confidence ≥80%
   - RAGU-RAGU: 40%-60% Mendukung, atau >50% Netral, Confidence 50%-79%
   - HOAX: <30% Mendukung, ≥50% Menyangkal, Confidence <50%

4. CONFIDENCE SCORE: 0-100 berdasarkan konsistensi dan kualitas sumber

FORMAT OUTPUT (JSON valid tanpa markdown):
{$jsonTemplate}

PENTING:
- Gunakan hanya informasi dari DATA_PENDUKUNG
- Berikan kutipan spesifik untuk setiap sumber
- Jangan tambahkan penjelasan di luar JSON
- Pastikan semua field terisi dengan data yang valid
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
                    
                    // Enhanced response dengan source analysis
                    $response = [
                        'success' => true,
                        'explanation' => (string) ($data['explanation'] ?? 'Tidak ada penjelasan tersedia'),
                        'detailed_analysis' => (string) ($data['detailed_analysis'] ?? $data['analysis'] ?? 'Tidak ada analisis tersedia'),
                        'claim' => (string) $claim,
                    ];
                    
                    // Add source analysis jika tersedia
                    if (!empty($data['source_analysis']) && is_array($data['source_analysis'])) {
                        $response['source_analysis'] = $data['source_analysis'];
                    }
                    
                    // Add statistics jika tersedia
                    if (!empty($data['statistics']) && is_array($data['statistics'])) {
                        $response['statistics'] = $data['statistics'];
                    }
                    
                    // Add accuracy score jika tersedia
                    if (!empty($data['accuracy_score']) && is_array($data['accuracy_score'])) {
                        $response['accuracy_score'] = $data['accuracy_score'];
                    }
                    
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
     * Bersihkan response dari markdown dan formatting
     */
    private function cleanResponse(string $text): string
    {
        // Hapus markdown code blocks
        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        
        // Hapus markdown formatting
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        
        // Hapus extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    /**
     * Parse response text jika JSON parsing gagal
     */
    private function parseTextResponse(string $text, string $claim): array
    {
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
        return [
            'success' => true,
            'explanation' => (string) $explanation,
            'sources' => (string) $sources,
            'analysis' => (string) $analysis,
            'claim' => (string) $claim,
        ];
    }

    /**
     * Fallback response jika API gagal
     */
    private function getFallbackResponse(string $claim): array
    {
        return [
            'success' => false,
            'explanation' => 'Tidak dapat menganalisis klaim ini saat ini. Silakan coba lagi nanti.',
            'sources' => '',
            'analysis' => 'Tidak ada analisis tersedia',
            'claim' => (string) $claim,
            'error' => 'Gemini API tidak tersedia'
        ];
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
        
        return [
            'success' => true,
            'explanation' => $explanation,
            'sources' => $sources,
            'analysis' => $analysis,
            'claim' => (string) $claim,
        ];
    }
}
