<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use function config;
use function env;
use function data_get;

/**
 * Service untuk berkomunikasi dengan Google Gemini AI
 * Menggunakan HTTP client untuk mengakses Gemini API
 */
class GeminiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    private bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY')) ?? '';
        $this->enabled = (bool) config('services.gemini.enabled', true);
        
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
        Log::info('GeminiService analyzeClaim called', [
            'claim' => $claim,
            'api_key_masked' => $maskedKey,
            'search_results_count' => count($searchResults)
        ]);

        // Saat environment lokal, hindari pemanggilan API eksternal dan gunakan fallback
        if (!$this->enabled) {
            Log::warning('GeminiService disabled by configuration, using fallback');
            return $this->getFallbackWithSearchData($claim, $searchResults);
        }
        
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
                $text = \data_get($data, 'candidates.0.content.parts.0.text');

                if (!is_string($text) || trim($text) === '') {
                    $blockReason = \data_get($data, 'promptFeedback.blockReason');
                    $safetyRatings = \data_get($data, 'promptFeedback.safetyRatings');
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

        } catch (ConnectionException $e) {
            Log::warning('Gemini API unreachable: ' . $e->getMessage());
            return $this->getFallbackWithSearchData($claim, $searchResults);
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            Log::error('Gemini Service Exception Trace: ' . $e->getTraceAsString());
            return $this->getFallbackWithSearchData($claim, $searchResults);
        }
    }

    /**
     * Membangun prompt untuk Gemini AI dengan data pencarian Google CSE
     */
    private function buildPrompt(string $claim, array $searchResults = []): string
    {
        $searchData = '';
        $sourceCount = 0;
        
        if (!empty($searchResults)) {
            $items = [];
            foreach ($searchResults as $index => $result) {
                $sourceCount++;
                $items[] = sprintf(
                    "SUMBER %d:\n  Domain: %s\n  Judul: %s\n  URL: %s\n  Ringkasan: %s",
                    $index + 1,
                    $result['displayLink'] ?? 'Tidak ada domain',
                    $result['title'] ?? 'Tidak ada judul',
                    $result['link'] ?? 'Tidak ada URL',
                    $result['snippet'] ?? 'Tidak ada snippet'
                );
            }
            $searchData = "\n\nDATA HASIL PENCARIAN GOOGLE CSE (" . $sourceCount . " sumber):\n" . implode("\n\n", $items);
        }

        $jsonTemplate = json_encode([
            'verdict' => 'DIDUKUNG_DATA|TIDAK_DIDUKUNG_DATA|MEMERLUKAN_VERIFIKASI',
            'explanation' => 'Penjelasan singkat kesimpulan (1-2 kalimat)',
            'analysis' => 'Analisis mendalam dengan menyebutkan sumber spesifik',
            'confidence' => 'tinggi|sedang|rendah',
            'sources_used' => ['domain1.com', 'domain2.com']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Anda adalah AI fact-checker profesional. Tugas Anda: Analisis klaim berdasarkan hasil pencarian Google CSE dan tentukan apakah klaim DIDUKUNG atau TIDAK DIDUKUNG oleh data.

=== KLAIM YANG HARUS DIANALISIS ===
"{$claim}"{$searchData}

=== INSTRUKSI ANALISIS ===

1. BACA SEMUA SUMBER:
   - Periksa setiap sumber dari DATA HASIL PENCARIAN GOOGLE CSE
   - Perhatikan domain sumber (kredibilitas media)
   - Analisis ringkasan/snippet dari setiap sumber

2. BANDINGKAN DENGAN KLAIM:
   - Apakah sumber-sumber MENDUKUNG klaim?
   - Apakah sumber-sumber MEMBANTAH klaim?
   - Apakah ada KONTRADIKSI antar sumber?

3. TENTUKAN VERDICT:
   - "DIDUKUNG_DATA": Jika mayoritas sumber terpercaya mendukung klaim
   - "TIDAK_DIDUKUNG_DATA": Jika mayoritas sumber terpercaya membantah klaim
   - "MEMERLUKAN_VERIFIKASI": Jika:
     * Data tidak cukup untuk menyimpulkan
     * Sumber saling bertentangan
     * Tidak ada sumber yang membahas klaim ini

4. TULIS ANALISIS:
   - Sebutkan sumber spesifik (nama domain, bukan nomor)
   - Contoh BENAR: "Menurut kompas.com, ..."
   - Contoh SALAH: "Menurut sumber nomor 1, ..."
   - Jelaskan apa yang dikatakan setiap sumber

5. CONFIDENCE LEVEL:
   - "tinggi": Banyak sumber kredibel, konsisten
   - "sedang": Beberapa sumber, cukup konsisten
   - "rendah": Sedikit sumber atau saling bertentangan

=== FORMAT OUTPUT ===

WAJIB output JSON murni tanpa markdown:
{$jsonTemplate}

=== CONTOH OUTPUT ===

Contoh 1 - Klaim Didukung:
{
  "verdict": "DIDUKUNG_DATA",
  "explanation": "Klaim ini didukung oleh beberapa sumber berita terpercaya yang mengonfirmasi informasi tersebut.",
  "analysis": "Berdasarkan hasil pencarian, detik.com melaporkan bahwa [isi berita]. Kompas.com juga mengonfirmasi hal serupa dengan menyebutkan [detail]. Kedua sumber ini konsisten dalam mendukung klaim yang diberikan.",
  "confidence": "tinggi",
  "sources_used": ["detik.com", "kompas.com"]
}

Contoh 2 - Klaim Tidak Didukung:
{
  "verdict": "TIDAK_DIDUKUNG_DATA",
  "explanation": "Klaim ini tidak didukung oleh data. Sumber-sumber terpercaya justru menunjukkan informasi yang berbeda.",
  "analysis": "Menurut tempo.co, [fakta yang berbeda]. CNN Indonesia juga menyatakan bahwa [informasi yang membantah]. Tidak ada sumber yang mendukung klaim ini.",
  "confidence": "tinggi",
  "sources_used": ["tempo.co", "cnnindonesia.com"]
}

Contoh 3 - Memerlukan Verifikasi:
{
  "verdict": "MEMERLUKAN_VERIFIKASI",
  "explanation": "Data yang tersedia tidak cukup untuk memverifikasi klaim ini. Diperlukan sumber tambahan.",
  "analysis": "Hasil pencarian hanya menampilkan satu sumber yang membahas topik terkait, yaitu tribunnews.com, namun tidak secara spesifik membahas klaim yang diberikan. Diperlukan lebih banyak sumber kredibel untuk verifikasi.",
  "confidence": "rendah",
  "sources_used": ["tribunnews.com"]
}

=== ATURAN PENTING ===
- HANYA output JSON, TIDAK ADA text lain
- JANGAN gunakan markdown code blocks
- WAJIB isi semua field (verdict, explanation, analysis, confidence, sources_used)
- Gunakan bahasa Indonesia yang jelas dan objektif
- Sebutkan nama domain sumber, bukan "sumber 1", "sumber 2"

Mulai analisis sekarang!
PROMPT;
    }

    /**
     * Parse response dari Gemini AI
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
                    
                    // Extract sources_used array and convert to string
                    $sourcesUsed = '';
                    if (isset($data['sources_used']) && is_array($data['sources_used'])) {
                        $sourcesUsed = implode(', ', $data['sources_used']);
                    }
                    
                    // Format social media links in explanation and analysis
                    $explanation = $this->formatSocialMediaLinks((string) ($data['explanation'] ?? 'Tidak ada penjelasan tersedia'));
                    $analysis = $this->formatSocialMediaLinks((string) ($data['analysis'] ?? 'Tidak ada analisis tersedia'));
                    
                    return [
                        'success' => true,
                        'verdict' => (string) ($data['verdict'] ?? 'MEMERLUKAN_VERIFIKASI'),
                        'explanation' => $explanation,
                        'analysis' => $analysis,
                        'confidence' => (string) ($data['confidence'] ?? 'rendah'),
                        'sources' => $sourcesUsed,
                        'claim' => (string) $claim,
                    ];
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
     * Format social media domain names to "Postingan di [Platform]"
     */
    private function formatSocialMediaLinks(string $text): string
    {
        // Replace social media domains with formatted text
        // Use word boundaries to avoid replacing partial matches
        $replacements = [
            '/\binstagram\.com\b/i' => 'postingan di Instagram',
            '/\bfacebook\.com\b/i' => 'postingan di Facebook',
            '/\bfb\.com\b/i' => 'postingan di Facebook',
            '/\btwitter\.com\b/i' => 'postingan di X',
            '/\bx\.com\b/i' => 'postingan di X',
            '/\byoutube\.com\b/i' => 'postingan di YouTube',
            '/\byoutu\.be\b/i' => 'postingan di YouTube',
            '/\breddit\.com\b/i' => 'postingan di Reddit',
            '/\btiktok\.com\b/i' => 'postingan di TikTok',
            '/\blinkedin\.com\b/i' => 'postingan di LinkedIn',
            '/\bthreads\.net\b/i' => 'postingan di Threads',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
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
            'verdict' => 'MEMERLUKAN_VERIFIKASI',
            'explanation' => 'Tidak dapat menganalisis klaim ini saat ini. Silakan coba lagi nanti.',
            'analysis' => 'Tidak ada analisis tersedia',
            'confidence' => 'rendah',
            'sources' => '',
            'claim' => (string) $claim,
            'error' => 'Gemini API tidak tersedia'
        ];
    }
    private function getFallbackWithSearchData(string $claim, array $searchResults = []): array
    {
        $explanation = 'Tidak dapat menganalisis klaim ini dengan AI saat ini.';
        $sources = '';
        $analysis = 'Tidak ada analisis tersedia';
        $verdict = 'MEMERLUKAN_VERIFIKASI';
        $confidence = 'rendah';
        
        if (!empty($searchResults)) {
            $explanation = 'Klaim ini memerlukan verifikasi lebih lanjut. Silakan periksa sumber-sumber berikut untuk informasi lebih detail.';
            
            // Extract domain names from search results
            $domains = [];
            foreach (array_slice($searchResults, 0, 3) as $result) {
                if (isset($result['displayLink'])) {
                    $domains[] = $result['displayLink'];
                }
            }
            $sources = implode(', ', $domains);
            
            $analysis = "Hasil pencarian Google CSE menampilkan beberapa sumber terkait:\n\n";
            foreach (array_slice($searchResults, 0, 3) as $index => $result) {
                $domain = $result['displayLink'] ?? 'Tidak ada domain';
                $title = $result['title'] ?? 'Tidak ada judul';
                $snippet = substr($result['snippet'] ?? 'Tidak ada snippet', 0, 150);
                $url = $result['link'] ?? 'Tidak ada URL';
                
                $analysis .= ($index + 1) . ". {$domain}\n";
                $analysis .= "   Judul: {$title}\n";
                $analysis .= "   Ringkasan: {$snippet}...\n";
                $analysis .= "   URL: {$url}\n\n";
            }
            $analysis .= "Untuk verifikasi yang akurat, silakan baca artikel lengkap dari sumber-sumber di atas. AI tidak dapat memberikan kesimpulan definitif tanpa analisis mendalam.";
            
            // Format social media links in analysis
            $analysis = $this->formatSocialMediaLinks($analysis);
        }
        
        return [
            'success' => true,
            'verdict' => $verdict,
            'explanation' => $this->formatSocialMediaLinks($explanation),
            'analysis' => $analysis,
            'confidence' => $confidence,
            'sources' => $sources,
            'claim' => (string) $claim,
        ];
    }
}
