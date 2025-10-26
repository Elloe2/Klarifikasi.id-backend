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
        Log::debug('GeminiService initialized with API Key: ' . $maskedKey);
        
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
        Log::debug('GeminiService analyzeClaim called with API Key: ' . $maskedKey);
        
        // Check API key validity
        if (empty($this->apiKey) || strlen($this->apiKey) < 20) {
            Log::warning('Gemini API Key not configured properly, using fallback');
            return $this->getFallbackWithSearchData($claim, $searchResults);
        }
        
        try {
            Log::debug('Sending request to Gemini API...');
            
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

            Log::debug('Gemini API Response Status: ' . $response->status());
            
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

                Log::debug('Gemini API Success - Response received');
                return $this->parseResponse((string) $text, $claim, $searchResults);
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
     */
    private function buildPrompt(string $claim, array $searchResults = []): string
    {
        $searchData = '';
        
        if (!empty($searchResults)) {
            $items = [];
            foreach ($searchResults as $index => $result) {
                $items[] = sprintf(
                    '%d. situs="%s" judul="%s" url="%s" ringkasan="%s" domain="%s"',
                    $index + 1,
                    $result['displayLink'] ?? 'Tidak ada domain',
                    $result['title'] ?? 'Tidak ada judul',
                    $result['link'] ?? 'Tidak ada URL',
                    $result['snippet'] ?? 'Tidak ada snippet',
                    $result['displayLink'] ?? 'Tidak ada domain'
                );
            }
            $searchData = "\n\nDATA_PENDUKUNG:\n" . implode("\n", $items);
        }

        $jsonTemplate = json_encode([
            'summary' => 'Ringkasan objektif tentang kebenaran klaim dalam <=3 kalimat',
            'analysis' => 'Analisis mendalam berdasarkan bukti yang tersedia',
            'verdict_explanation' => 'Penjelasan singkat bagaimana simpulan dibuat',
            'sources_breakdown' => [
                [
                    'source_reference' => 'Nama domain atau judul sumber',
                    'stance' => 'SUPPORT|OPPOSE|NEUTRAL',
                    'reasoning' => 'Ringkasan alasan dari sumber terkait klaim',
                    'quote' => 'Cuplikan ringkas bila tersedia',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Anda adalah pakar pemeriksa fakta. Analisis klaim berikut secara objektif dan ringkas dalam bahasa Indonesia.

KLAIM: "{$claim}"{$searchData}

INSTRUKSI:
- Gunakan hanya informasi dari DATA_PENDUKUNG di atas.
- Jika data tidak cukup, nyatakan bahwa bukti tidak memadai.
- Tentukan stance setiap sumber (SUPPORT, OPPOSE, atau NEUTRAL) terhadap klaim berdasarkan isinya.
- Berikan reasoning yang jelas dan quote singkat (jika ada) untuk tiap sumber.
- Jangan tambahkan penjelasan di luar struktur JSON.

FORMAT OUTPUT (JSON valid tanpa markdown):
{$jsonTemplate}
PROMPT;
    }

    /**
     * Parse response dari Gemini AI
     */
    private function parseResponse(string $text, string $claim, array $searchResults = []): array
    {
        try {
            // Log response untuk debugging
            Log::debug('Gemini Raw Response length: ' . strlen($text));
            
            // Bersihkan response dari markdown formatting jika ada
            $cleanText = $this->cleanResponse($text);
            
            // Coba extract JSON dari response
            $jsonStart = strpos($cleanText, '{');
            $jsonEnd = strrpos($cleanText, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($cleanText, $jsonStart, $jsonEnd - $jsonStart + 1);
                Log::debug('Extracted JSON length: ' . strlen($jsonString));

                $sanitizedJson = $this->sanitizeJsonString($jsonString);
                if ($sanitizedJson !== $jsonString) {
                    Log::debug('Sanitized JSON string applied');
                }
                
                $data = json_decode($sanitizedJson, true);
                
                if ($data) {
                    Log::debug('Successfully parsed JSON response');
                    return $this->normalizeAnalysisData($data, $claim, $searchResults);
                } else {
                    Log::warning('Failed to decode Gemini JSON', [
                        'error' => json_last_error_msg(),
                        'snippet' => substr($sanitizedJson, 0, 300),
                    ]);
                }
            } else {
                Log::warning('No JSON found in response');
            }

            // Fallback jika JSON parsing gagal - coba parse manual
            Log::debug('Falling back to text parsing', [
                'raw_snippet' => substr($cleanText, 0, 300),
            ]);
            return $this->parseTextResponse($cleanText, $claim, $searchResults);

        } catch (\Exception $e) {
            Log::error('Error parsing Gemini response: ' . $e->getMessage());
            return $this->getFallbackResponse($claim, $searchResults);
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

    private function sanitizeJsonString(string $json): string
    {
        // Hapus tanda kutip ganda dalam key seperti ""summary"" menjadi "summary"
        $json = preg_replace('/""([^"]+)""\s*:/', '"$1":', $json);

        // Hapus space sebelum titik dua pada key
        $json = preg_replace('/"([^"]+)"\s+:/', '"$1":', $json);

        return trim($json);
    }

    /**
     * Parse response text jika JSON parsing gagal
     */
    private function parseTextResponse(string $text, string $claim, array $searchResults = []): array
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
        $base = [
            'summary' => (string) $explanation,
            'analysis' => (string) $analysis,
            'verdict_explanation' => 'Respons AI tidak dalam format terstruktur, gunakan hasil pencarian manual.',
            'sources_breakdown' => [],
        ];

        return $this->normalizeAnalysisData($base, $claim, $searchResults);
    }

    /**
     * Fallback response jika API gagal
     */
    private function getFallbackResponse(string $claim, array $searchResults = []): array
    {
        return $this->normalizeAnalysisData([
            'summary' => 'Tidak dapat menganalisis klaim ini saat ini.',
            'analysis' => 'Tidak ada analisis tersedia',
            'verdict_explanation' => 'Analisis AI tidak tersedia, gunakan sumber manual.',
            'sources_breakdown' => [],
            'error' => 'Gemini API tidak tersedia',
        ], $claim, $searchResults, false);
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
        
        return $this->normalizeAnalysisData([
            'summary' => $explanation,
            'analysis' => $analysis,
            'verdict_explanation' => 'Analisis AI tidak tersedia, klasifikasi ditetapkan sebagai ambigu.',
            'sources_breakdown' => $this->buildNeutralBreakdownFromSearch($searchResults),
        ], $claim, $searchResults);
    }

    private function normalizeAnalysisData(array $data, string $claim, array $searchResults = [], bool $success = true): array
    {
        $summary = (string) ($data['summary'] ?? $data['explanation'] ?? 'Tidak ada penjelasan tersedia');
        $analysis = (string) ($data['analysis'] ?? 'Tidak ada analisis tersedia');
        $verdictExplanation = (string) ($data['verdict_explanation'] ?? '');
        $rawSources = $data['sources_breakdown'] ?? [];
        $sourceBreakdown = $this->formatSourceBreakdown($rawSources);

        if (empty($sourceBreakdown) && !empty($searchResults)) {
            $sourceBreakdown = $this->buildNeutralBreakdownFromSearch($searchResults);
        }

        $aggregates = $this->calculateSourceAggregates($sourceBreakdown);

        if ($aggregates['verdict_label'] === 'RAGU-RAGU' && $aggregates['supporting'] === 0) {
            $negativeIndicators = [
                'tidak didukung',
                'tidak benar',
                'tidak ada bukti',
                'hoax',
                'klaim palsu',
                'tidak terbukti',
            ];

            $summaryLower = mb_strtolower($summary, 'UTF-8');
            $analysisLower = mb_strtolower($analysis, 'UTF-8');

            foreach ($negativeIndicators as $indicator) {
                if (str_contains($summaryLower, $indicator) || str_contains($analysisLower, $indicator)) {
                    $aggregates['verdict_label'] = 'HOAX';
                    $aggregates['verdict_score'] = 0.0;
                    if ($verdictExplanation === '') {
                        $verdictExplanation = 'Gemini menyatakan klaim ini tidak didukung bukti sehingga diklasifikasikan sebagai hoax.';
                    }
                    break;
                }
            }
        }

        if ($verdictExplanation === '') {
            $verdictExplanation = $this->buildVerdictExplanation($aggregates, $sourceBreakdown, $summary);
        }

        $sourcesText = $this->buildSourcesText($sourceBreakdown);

        return [
            'success' => $success,
            'claim' => (string) $claim,
            'explanation' => $summary,
            'analysis' => $analysis,
            'sources' => $sourcesText,
            'verdict' => [
                'label' => $aggregates['verdict_label'],
                'score' => $aggregates['verdict_score'],
                'reason' => $verdictExplanation,
                'supporting_sources' => $aggregates['supporting'],
                'opposing_sources' => $aggregates['opposing'],
                'neutral_sources' => $aggregates['neutral'],
                'total_sources' => $aggregates['total'],
            ],
            'source_breakdown' => $sourceBreakdown,
            'error' => $data['error'] ?? null,
        ];
    }

    private function formatSourceBreakdown(mixed $rawSources): array
    {
        if (!is_array($rawSources)) {
            return [];
        }

        $formatted = [];
        foreach ($rawSources as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $formatted[] = [
                'index' => $index + 1,
                'source_reference' => (string) ($item['source_reference'] ?? ''),
                'stance' => strtoupper((string) ($item['stance'] ?? 'NEUTRAL')),
                'reasoning' => (string) ($item['reasoning'] ?? 'Tidak ada penjelasan tersedia'),
                'quote' => isset($item['quote']) ? (string) $item['quote'] : null,
            ];
        }

        return $formatted;
    }

    private function buildNeutralBreakdownFromSearch(array $searchResults): array
    {
        if (empty($searchResults)) {
            return [];
        }

        $breakdown = [];
        foreach ($searchResults as $index => $result) {
            $breakdown[] = [
                'index' => $index + 1,
                'source_reference' => (string) ($result['displayLink'] ?? $result['title'] ?? 'Sumber'),
                'stance' => 'NEUTRAL',
                'reasoning' => 'Tidak ada analisis AI pada sumber ini. Lakukan verifikasi manual.',
                'quote' => isset($result['snippet']) ? substr((string) $result['snippet'], 0, 180) : null,
            ];
        }

        return $breakdown;
    }

    private function calculateSourceAggregates(array $sourceBreakdown): array
    {
        $supporting = 0;
        $opposing = 0;
        $neutral = 0;

        foreach ($sourceBreakdown as $source) {
            $stance = $source['stance'] ?? 'NEUTRAL';
            if ($stance === 'SUPPORT') {
                $supporting++;
            } elseif ($stance === 'OPPOSE' || $stance === 'OPPOSING') {
                $opposing++;
            } else {
                $neutral++;
            }
        }

        $total = $supporting + $opposing + $neutral;
        $supportRatio = $total > 0 ? $supporting / $total : 0.0;
        $opposeRatio = $total > 0 ? $opposing / $total : 0.0;

        $verdictLabel = 'RAGU-RAGU';
        if ($supportRatio >= 0.6 && $supporting >= $opposing + 1) {
            $verdictLabel = 'FAKTA';
        } elseif ($opposeRatio >= 0.5 && $opposing >= $supporting + 1) {
            $verdictLabel = 'HOAX';
        }

        $verdictScore = round($supportRatio * 100, 2);

        return [
            'supporting' => $supporting,
            'opposing' => $opposing,
            'neutral' => $neutral,
            'total' => $total,
            'verdict_label' => $verdictLabel,
            'verdict_score' => $verdictScore,
        ];
    }

    private function buildVerdictExplanation(array $aggregates, array $sourceBreakdown, string $summary): string
    {
        $supporting = $aggregates['supporting'];
        $opposing = $aggregates['opposing'];
        $neutral = $aggregates['neutral'];

        if ($aggregates['total'] === 0) {
            return 'Tidak ada sumber yang dapat dianalisis untuk menentukan verdict.';
        }

        $parts = [];
        $parts[] = sprintf('Mendukung: %d sumber, Menentang: %d, Netral: %d.', $supporting, $opposing, $neutral);

        if ($aggregates['verdict_label'] === 'FAKTA') {
            $parts[] = 'Mayoritas sumber mendukung klaim sehingga diklasifikasikan sebagai fakta.';
        } elseif ($aggregates['verdict_label'] === 'HOAX') {
            $parts[] = 'Mayoritas sumber menentang klaim sehingga diklasifikasikan sebagai hoax.';
        } else {
            $parts[] = 'Distribusi sumber tidak cukup jelas, sehingga dianggap ambigu.';
        }

        if ($summary !== '') {
            $parts[] = 'Ringkasan: ' . $summary;
        }

        return implode(' ', $parts);
    }

    private function buildSourcesText(array $sourceBreakdown): string
    {
        if (empty($sourceBreakdown)) {
            return '';
        }

        $references = [];
        foreach ($sourceBreakdown as $source) {
            $reference = trim((string) ($source['source_reference'] ?? ''));
            if ($reference !== '') {
                $references[] = $reference;
            }
        }

        $references = array_unique($references);

        return implode(', ', $references);
    }
}
