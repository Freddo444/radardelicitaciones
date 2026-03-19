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
     * Fetch documents for a process code.
     */
    public function fetchDocuments(string $processCode): array
    {
        $response = $this->get('/procesos/documentos', ['proceso' => $processCode]);

        return $response['payload']['content'] ?? [];
    }

    /**
     * Fetch articles/line items for a process code.
     */
    public function fetchProcessArticles(string $processCode): array
    {
        $response = $this->get('/procesos/articulos', ['proceso' => $processCode]);

        return $response['payload']['content'] ?? [];
    }

    /**
     * Fetch contracts for a process code.
     */
    public function fetchContracts(string $processCode): array
    {
        $response = $this->get('/contratos', ['proceso' => $processCode]);

        return $response['payload']['content'] ?? [];
    }

    /**
     * Fetch awarded articles for a process code.
     */
    public function fetchContractArticles(string $processCode): array
    {
        $response = $this->get('/contratos/articulos', ['proceso' => $processCode]);

        return $response['payload']['content'] ?? [];
    }

    /**
     * Fetch contract articles filtered by UNSPSC level, paginated.
     * Returns all pages up to $maxPages.
     */
    public function fetchContractArticlesByRubro(string $code, string $level, int $maxPages = 50): Collection
    {
        $paramKey = match ($level) {
            'familia' => 'familia',
            'subclase' => 'subclase',
            default => 'clase',
        };

        $results = collect();
        $page = 0;

        do {
            $response = $this->get('/contratos/articulos', [
                $paramKey => (int) $code,
                'page' => $page,
                'limit' => 100,
            ]);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $results = $results->merge($items);

            $totalPages = $response['pages'] ?? 1;
            $page++;

            if ($page < $totalPages && $page < $maxPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page < $totalPages && $page < $maxPages);

        return $results;
    }

    /**
     * Fetch contracts paginated. Supports filtering by unidad_compra, rpe, proceso.
     */
    public function fetchContractsPaginated(array $filters = [], int $maxPages = 50): Collection
    {
        $results = collect();
        $page = 0;

        do {
            $params = array_merge($filters, ['page' => $page, 'limit' => 100]);
            $response = $this->get('/contratos', $params);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $results = $results->merge($items);

            $totalPages = $response['pages'] ?? 1;
            $page++;

            if ($page < $totalPages && $page < $maxPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page < $totalPages && $page < $maxPages);

        return $results;
    }

    /**
     * Fetch PACC plans (annual purchase plans), paginated.
     */
    public function fetchPaccPlans(?int $year = null, ?int $unidadCompra = null, int $maxPages = 50): Collection
    {
        $results = collect();
        $page = 0;

        do {
            $params = array_filter([
                'año' => $year ?? (int) date('Y'),
                'unidad_compra' => $unidadCompra,
                'page' => $page,
                'limit' => 100,
            ], fn ($v) => $v !== null);

            $response = $this->get('/pacc', $params);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $results = $results->merge($items);

            $totalPages = $response['pages'] ?? 1;
            $page++;

            if ($page < $totalPages && $page < $maxPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page < $totalPages && $page < $maxPages);

        return $results;
    }

    /**
     * Fetch PACC acquisitions (planned purchases within a PACC), paginated.
     */
    public function fetchPaccAcquisitions(?int $year = null, ?int $unidadCompra = null, ?string $paccId = null, int $maxPages = 100): Collection
    {
        $results = collect();
        $page = 0;

        do {
            $params = array_filter([
                'año' => $year ?? (int) date('Y'),
                'unidad_compra' => $unidadCompra,
                'pacc_id' => $paccId,
                'page' => $page,
                'limit' => 100,
            ], fn ($v) => $v !== null);

            $response = $this->get('/pacc/adquisiciones', $params);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $results = $results->merge($items);

            $totalPages = $response['pages'] ?? 1;
            $page++;

            if ($page < $totalPages && $page < $maxPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page < $totalPages && $page < $maxPages);

        return $results;
    }

    /**
     * Fetch providers (suppliers), paginated.
     */
    public function fetchProviders(array $filters = [], int $maxPages = 200): Collection
    {
        $results = collect();
        $page = 0;

        do {
            $params = array_merge($filters, ['page' => $page, 'limit' => 100]);
            $response = $this->get('/proveedores', $params);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $results = $results->merge($items);

            $totalPages = $response['pages'] ?? 1;
            $page++;

            if ($page < $totalPages && $page < $maxPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page < $totalPages && $page < $maxPages);

        return $results;
    }

    /**
     * Fetch purchasing units (institutions), paginated.
     */
    public function fetchInstitutions(int $maxPages = 100): Collection
    {
        $results = collect();
        $page = 0;

        do {
            $params = ['page' => $page, 'limit' => 100];
            $response = $this->get('/unidades_compra', $params);

            $items = collect($response['payload']['content'] ?? []);
            if ($items->isEmpty()) {
                break;
            }

            $results = $results->merge($items);

            $totalPages = $response['pages'] ?? 1;
            $page++;

            if ($page < $totalPages && $page < $maxPages) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }

        } while ($page < $totalPages && $page < $maxPages);

        return $results;
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
