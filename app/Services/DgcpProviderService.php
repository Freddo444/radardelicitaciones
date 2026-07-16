<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Reads a provider's data from the DGCP open-data API by RPE. Shared by the
 * onboarding RPE lookup and the "sync rubros" action so both pull the same
 * canonical rubro list from the DGCP — rubros are never free-typed, which
 * keeps monitoring scoped to what the company is actually registered for.
 */
class DgcpProviderService
{
    private const BASE = 'https://datosabiertos.dgcp.gob.do/api-dgcp/v1';

    /**
     * Fetch the familia-level rubros a provider is registered for at the DGCP.
     *
     * @return array<int, array{code: string, name: string}> deduped by code
     */
    public function fetchRubros(int $rpe): array
    {
        $rubros = [];
        $page = 1;

        do {
            $response = Http::timeout(10)->get(self::BASE.'/proveedores/rubro', [
                'rpe' => $rpe,
                'limit' => 100,
                'page' => $page,
            ]);

            if ($response->failed()) {
                break;
            }

            foreach ($response->json('payload.content', []) as $r) {
                $code = $r['familia_unspsc'] ?? null;
                if ($code) {
                    $rubros[] = ['code' => (string) $code, 'name' => trim((string) ($r['descripcion'] ?? ''))];
                }
            }

            $totalPages = (int) $response->json('pages', 1);
            $page++;
        } while ($page <= $totalPages);

        $seen = [];

        return array_values(array_filter($rubros, function (array $r) use (&$seen) {
            if ($r['code'] === '' || isset($seen[$r['code']])) {
                return false;
            }
            $seen[$r['code']] = true;

            return true;
        }));
    }
}
