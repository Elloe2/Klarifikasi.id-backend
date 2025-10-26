<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSearchService
{
    /**
     * @throws RuntimeException
     */
    public function search(string $query): array
    {
        // TEMPORARY: Mock data untuk local testing karena quota habis
        if (app()->environment('local') || config('app.debug')) {
            return $this->getMockResults($query);
        }

        $key = config('services.google_cse.key');
        $cx = config('services.google_cse.cx');

        if (blank($key) || blank($cx)) {
            throw new RuntimeException('Google Custom Search credentials are not configured.');
        }

        $verifySsl = config('services.google_cse.verify_ssl', false);

        try {
            $response = Http::withOptions([
                'verify' => $verifySsl,
            ])->baseUrl('https://www.googleapis.com/customsearch/v1')
                ->get('', [
                    'key' => $key,
                    'cx' => $cx,
                    'q' => $query,
                    'num' => 10,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Unable to reach Google Custom Search API.', 0, $exception);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? 'Google Custom Search API returned an error.';
            throw new RuntimeException($message, $response->status());
        }

        $items = $response->json('items', []);

        return collect($items)->map(function (array $item) {
            $thumbnail = Arr::get($item, 'pagemap.cse_thumbnail.0.src')
                ?? Arr::get($item, 'pagemap.metatags.0.og:image')
                ?? Arr::get($item, 'pagemap.metatags.0.twitter:image')
                ?? Arr::get($item, 'pagemap.metatags.0.twitter:image:src');

            return [
                'title' => Arr::get($item, 'title'),
                'snippet' => Arr::get($item, 'snippet'),
                'link' => Arr::get($item, 'link'),
                'displayLink' => Arr::get($item, 'displayLink'),
                'formattedUrl' => Arr::get($item, 'formattedUrl'),
                'thumbnail' => $thumbnail,
            ];
        })->values()->all();
    }

    /**
     * Mock results untuk local testing
     */
    private function getMockResults(string $query): array
    {
        return [
            [
                'title' => 'Contoh Berita 1 - ' . $query,
                'snippet' => 'Ini adalah contoh snippet berita untuk testing lokal. Berita ini membahas tentang ' . $query . ' dengan detail lengkap.',
                'link' => 'https://example.com/berita-1',
                'displayLink' => 'example.com',
                'formattedUrl' => 'https://example.com/berita-1',
                'thumbnail' => null,
            ],
            [
                'title' => 'Artikel Terkait ' . $query . ' - Portal Berita',
                'snippet' => 'Portal berita terpercaya memberikan informasi terkini tentang ' . $query . '. Simak selengkapnya di artikel ini.',
                'link' => 'https://news.example.com/artikel-2',
                'displayLink' => 'news.example.com',
                'formattedUrl' => 'https://news.example.com/artikel-2',
                'thumbnail' => null,
            ],
            [
                'title' => 'Fakta dan Mitos: ' . $query,
                'snippet' => 'Artikel ini membahas fakta dan mitos seputar ' . $query . ' berdasarkan sumber terpercaya.',
                'link' => 'https://factcheck.example.com/fakta',
                'displayLink' => 'factcheck.example.com',
                'formattedUrl' => 'https://factcheck.example.com/fakta',
                'thumbnail' => null,
            ],
        ];
    }
}
