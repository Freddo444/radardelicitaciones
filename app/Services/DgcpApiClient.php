<?php

namespace App\Services;

use App\Exceptions\DgcpApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DgcpApiClient
{
    private const BASE_URL = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

    private const REQUEST_DELAY_MS = 4500; // safely under 15 req/min

    private const RATE_LIMIT_WAIT = 65;   // seconds to sleep on 429

    /**
     * Fetch articles for a rubro published since a given datetime.
     * Supports familia, clase, subclase levels (API types them as int).
     * Stops paginating early once a full page contains no recent articles.
     */
    public function fetchArticlesSince(string $code, string $level, \DateTime $since): Collection
    {
        // /procesos/articulos supports familia, clase, subclase — not segmento
        $paramKey = match ($level) {
            'familia' => 'familia',
            'subclase' => 'subclase',
            default => 'clase',
        };

        $results = collect();
        $page = 1;

        do {
            $response = $this->get('/procesos/articulos', [
                $paramKey => (int) $code,   // API expects int
                'page' => $page,
                'limit' => 1000,
            ]);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $inWindow = $items->filter(function ($item) use ($since) {
                $pub = $item['fecha_publicacion'] ?? null;
                if (! $pub) {
                    return false;
                }
                try {
                    return new \DateTime($pub) >= $since;
                } catch (\Exception) {
                    return false;
                }
            });

            $results = $results->merge($inWindow);

            // Articles come newest-first; stop once a full page is older than the window
            $hasRecent = $items->contains(function ($item) use ($since) {
                $pub = $item['fecha_publicacion'] ?? null;
                if (! $pub) {
                    return false;
                }
                try {
                    return new \DateTime($pub) >= $since;
                } catch (\Exception) {
                    return false;
                }
            });

            if (! $hasRecent) {
                break;
            }

            $totalPages = $response['pages'] ?? 1;
            $page++;
            if ($page <= $totalPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page <= $totalPages);

        return $results;
    }

    /**
     * Search the UNSPSC catalog.
     */
    public function searchCatalog(
        ?string $segmento = null,
        ?string $familia = null,
        ?string $clase = null,
        ?string $subclase = null,
        int $page = 1,
        int $limit = 50
    ): array {
        $params = array_filter([
            'segmento' => $segmento,
            'familia' => $familia,
            'clase' => $clase,
            'subclase' => $subclase,
            'page' => $page,
            'limit' => $limit,
        ], fn ($v) => $v !== null);

        return $this->get('/catalogo', $params);
    }

    /**
     * Fetch a single process by its code.
     */
    public function fetchProcessByCode(string $code): ?array
    {
        $response = $this->get('/procesos', ['proceso' => $code]);

        return $response['payload']['content'][0] ?? null;
    }

    /**
     * Test API connectivity.
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->get('/procesos', ['limit' => 1, 'page' => 1]);

            return isset($response['code']) && $response['code'] === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Perform a GET request. Handles 429 by sleeping and retrying once.
     */
    private function get(string $endpoint, array $params = []): array
    {
        $url = self::BASE_URL.$endpoint;

        $response = Http::timeout(30)->get($url, $params);

        if ($response->status() === 429) {
            Log::warning("[DGCP] Rate limited on {$endpoint}, sleeping ".self::RATE_LIMIT_WAIT.'s');
            sleep(self::RATE_LIMIT_WAIT);
            $response = Http::timeout(30)->get($url, $params);
        }

        if ($response->failed()) {
            throw new DgcpApiException(
                "DGCP API error on {$endpoint}: HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new DgcpApiException("DGCP API returned non-JSON response on {$endpoint}");
        }

        return $data;
    }
}
