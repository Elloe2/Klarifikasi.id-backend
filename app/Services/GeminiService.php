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

        return "Analisis klaim berikut dengan menggunakan data pencarian Google yang tersedia dan berikan penjelasan yang objektif:

KLAIM: \"{$claim}\"{$searchData}

TUGAS:
1. Analisis klaim berdasarkan data pencarian Google di atas
2. Identifikasi apakah ada informasi yang mendukung atau membantah klaim
3. Berikan penjelasan yang objektif dan seimbang
4. Sertakan sumber-sumber yang relevan dari hasil pencarian

Berikan jawaban dalam format JSON:
{
  \"explanation\": \"Penjelasan objektif tentang klaim berdasarkan data pencarian\",
  \"sources\": \"Sumber-sumber yang relevan dari hasil pencarian Google\",
  \"analysis\": \"Analisis mendalam tentang klaim berdasarkan data yang tersedia\"
}

Jawaban harus dalam bahasa Indonesia, objektif, dan berdasarkan data pencarian yang tersedia.";
    }

    /**
     * Parse response dari Gemini AI
     */
    private function parseResponse(string $text, string $claim): array
    {
        try {
            // Coba extract JSON dari response
            $jsonStart = strpos($text, '{');
            $jsonEnd = strrpos($text, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonString, true);
                
                if ($data && isset($data['explanation'])) {
                    return [
                        'success' => true,
                        'explanation' => $data['explanation'] ?? 'Tidak ada penjelasan tersedia',
                        'sources' => $data['sources'] ?? 'Tidak ada sumber tersedia',
                        'analysis' => $data['analysis'] ?? 'Tidak ada analisis tersedia',
                        'claim' => $claim,
                    ];
                }
            }
            
            // Fallback jika JSON parsing gagal
            return $this->parseTextResponse($text, $claim);
            
        } catch (\Exception $e) {
            Log::error('Error parsing Gemini response: ' . $e->getMessage());
            return $this->getFallbackResponse($claim);
        }
    }

    /**
     * Parse response text jika JSON parsing gagal
     */
    private function parseTextResponse(string $text, string $claim): array
    {
        return [
            'success' => true,
            'explanation' => 'Tidak dapat menganalisis klaim ini dengan pasti.',
            'sources' => 'Analisis AI Gemini',
            'analysis' => 'Tidak ada analisis tersedia',
            'claim' => $claim,
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
            'claim' => $claim,
            'error' => 'Gemini API tidak tersedia'
        ];
    }
}
