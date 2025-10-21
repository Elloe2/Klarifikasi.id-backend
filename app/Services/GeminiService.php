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
    }

    /**
     * Menganalisis klaim dengan menggunakan hasil pencarian Google CSE
     */
    public function analyzeClaim(string $claim, array $searchResults = []): array
    {
        try {
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

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return $this->parseResponse($text, $claim);
            } else {
                Log::error('Gemini API Error: ' . $response->body());
                return $this->getFallbackResponse($claim);
            }

        } catch (\Exception $e) {
            Log::error('Gemini Service Error: ' . $e->getMessage());
            return $this->getFallbackResponse($claim);
        }
    }

    /**
     * Membangun prompt untuk Gemini AI dengan data pencarian Google CSE
     */
    private function buildPrompt(string $claim, array $searchResults = []): string
    {
        $searchData = '';
        
        if (!empty($searchResults)) {
            $searchData = "\n\nHASIL PENCARIAN GOOGLE:\n";
            foreach ($searchResults as $index => $result) {
                $searchData .= ($index + 1) . ". " . ($result['title'] ?? 'Tidak ada judul') . "\n";
                $searchData .= "   URL: " . ($result['link'] ?? 'Tidak ada URL') . "\n";
                $searchData .= "   Snippet: " . ($result['snippet'] ?? 'Tidak ada snippet') . "\n";
                $searchData .= "   Domain: " . ($result['displayLink'] ?? 'Tidak ada domain') . "\n\n";
            }
        }

        return "Analisis klaim berikut dengan menggunakan data pencarian Google yang tersedia:

KLAIM: \"{$claim}\"{$searchData}

TUGAS:
1. Analisis klaim berdasarkan data pencarian Google di atas
2. Berikan penjelasan objektif tentang klaim
3. Sertakan sumber-sumber yang relevan dari hasil pencarian

Berikan jawaban dalam format JSON yang valid:
{
  \"explanation\": \"Penjelasan singkat dan objektif tentang klaim\",
  \"sources\": \"Sumber-sumber yang relevan dari hasil pencarian\",
  \"analysis\": \"Analisis mendalam tentang klaim berdasarkan data yang tersedia\"
}

WAJIB menggunakan format JSON di atas tanpa markdown atau formatting tambahan. Jawaban dalam bahasa Indonesia.";
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
                    return [
                        'success' => true,
                        'explanation' => (string) ($data['explanation'] ?? 'Tidak ada penjelasan tersedia'),
                        'sources' => (string) ($data['sources'] ?? 'Tidak ada sumber tersedia'),
                        'analysis' => (string) ($data['analysis'] ?? 'Tidak ada analisis tersedia'),
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
     * Parse response text jika JSON parsing gagal
     */
    private function parseTextResponse(string $text, string $claim): array
    {
        // Jika response tidak dalam format JSON, coba extract informasi manual
        $explanation = 'Tidak dapat menganalisis klaim ini dengan pasti.';
        $sources = 'Analisis AI Gemini';
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
            
            $sources = 'Berdasarkan analisis AI Gemini dan data pencarian Google';
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
            'sources' => 'Sistem sedang mengalami gangguan',
            'analysis' => 'Tidak ada analisis tersedia',
            'claim' => (string) $claim,
            'error' => 'Gemini API tidak tersedia'
        ];
    }
}
