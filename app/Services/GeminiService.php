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
     * Menganalisis klaim dan memberikan jawaban fakta atau hoax
     */
    public function analyzeClaim(string $claim): array
    {
        try {
            $prompt = $this->buildPrompt($claim);
            
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
     * Membangun prompt untuk Gemini AI
     */
    private function buildPrompt(string $claim): string
    {
        return "Sebagai AI fact-checker untuk Klarifikasi.id, analisis klaim berikut dan berikan jawaban yang singkat dan jelas:

KLAIM: \"{$claim}\"

Instruksi:
1. Analisis klaim tersebut berdasarkan pengetahuan umum dan fakta yang dapat diverifikasi
2. Berikan jawaban dalam format JSON dengan struktur:
{
  \"verdict\": \"FAKTA\" atau \"HOAX\",
  \"confidence\": \"Tinggi\", \"Sedang\", atau \"Rendah\",
  \"explanation\": \"Penjelasan singkat mengapa klaim ini fakta atau hoax\",
  \"sources\": \"Sumber atau referensi yang mendukung analisis\"
}

3. Jika klaim ambigu atau tidak dapat diverifikasi dengan pasti, gunakan \"HOAX\" dengan confidence \"Rendah\"
4. Fokus pada analisis yang objektif dan dapat dipertanggungjawabkan
5. Jawaban harus dalam bahasa Indonesia

Contoh format jawaban:
{
  \"verdict\": \"FAKTA\",
  \"confidence\": \"Tinggi\",
  \"explanation\": \"Klaim ini benar berdasarkan data resmi yang dapat diverifikasi\",
  \"sources\": \"Data resmi dari sumber terpercaya\"
}";
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
                
                if ($data && isset($data['verdict'])) {
                    return [
                        'success' => true,
                        'verdict' => $data['verdict'],
                        'confidence' => $data['confidence'] ?? 'Sedang',
                        'explanation' => $data['explanation'] ?? 'Tidak ada penjelasan tersedia',
                        'sources' => $data['sources'] ?? 'Tidak ada sumber tersedia',
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
        $verdict = 'HOAX';
        $confidence = 'Rendah';
        $explanation = 'Tidak dapat menganalisis klaim ini dengan pasti.';
        
        // Simple keyword detection
        if (stripos($text, 'fakta') !== false || stripos($text, 'benar') !== false) {
            $verdict = 'FAKTA';
            $confidence = 'Sedang';
        }
        
        return [
            'success' => true,
            'verdict' => $verdict,
            'confidence' => $confidence,
            'explanation' => $explanation,
            'sources' => 'Analisis AI Gemini',
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
            'verdict' => 'HOAX',
            'confidence' => 'Rendah',
            'explanation' => 'Tidak dapat menganalisis klaim ini saat ini. Silakan coba lagi nanti.',
            'sources' => 'Sistem sedang mengalami gangguan',
            'claim' => $claim,
            'error' => 'Gemini API tidak tersedia'
        ];
    }
}
