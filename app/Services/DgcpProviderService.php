<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
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
     * Is this RNC in the DGCP supplier registry?
     *
     * Returns null when we could not find out — the API was down, or the RNC is
     * not a shape worth asking about. Null and false are different answers and
     * callers must not conflate them: the registry lags a newly incorporated
     * business by up to a couple of months, so false means "not there yet",
     * not "not real". It is a flag for review, never grounds for refusal.
     *
     * numero_documento is the only parameter that actually filters this
     * endpoint. Passing rnc= is silently ignored and returns the full 128k-row
     * list, whose first record would look like a match for anything.
     */
    public function isRegistered(?string $rnc, ?int $rpe = null): ?bool
    {
        // Ask the rubro endpoint first when we have an RPE. /proveedores answers
        // HTTP 500 for some genuinely registered suppliers — Grupo Alzare among
        // them, seven months after incorporation, on both the rpe and the
        // numero_documento parameter — while /proveedores/rubro returns that
        // same company without complaint. A supplier with declared rubros is
        // registered by definition, so a hit here settles it.
        if ($rpe && $this->rpeHasRubros($rpe) === true) {
            return true;
        }

        $digits = preg_replace('/\D+/', '', (string) $rnc);

        if (! in_array(strlen((string) $digits), [9, 11], true)) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)->timeout(10)->get(self::BASE.'/proveedores', [
                'numero_documento' => $digits,
                'limit' => 1,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $content = $response->json('payload.content');

        if (! is_array($content)) {
            return false;
        }

        foreach ($content as $provider) {
            $found = preg_replace('/\D+/', '', (string) ($provider['numero_documento'] ?? ''));

            // Confirm the row actually carries the number we asked for, rather
            // than trusting that the filter was applied.
            if ($found === $digits) {
                return true;
            }
        }

        return false;
    }

    /**
     * The registered name for an RPE, read from the rubro endpoint.
     *
     * Every rubro row carries the supplier's razon_social, which makes this the
     * only way to identify a company whose /proveedores record returns HTTP 500
     * — Grupo Alzare's does, on every parameter.
     */
    public function razonSocialByRpe(int $rpe): ?string
    {
        try {
            $response = Http::connectTimeout(5)->timeout(10)->get(self::BASE.'/proveedores/rubro', [
                'rpe' => $rpe,
                'limit' => 1,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $content = $response->json('payload.content');
        $name = is_array($content) && isset($content[0]['razon_social'])
            ? trim((string) $content[0]['razon_social'])
            : '';

        return $name === '' ? null : $name;
    }

    /**
     * Does this RPE have any rubros declared at the DGCP?
     *
     * True is a positive confirmation of registration. Empty is not a denial —
     * a supplier may be registered without having declared any rubro — so it
     * returns false and lets the caller fall through to the RNC lookup rather
     * than concluding anything.
     */
    private function rpeHasRubros(int $rpe): ?bool
    {
        try {
            $response = Http::connectTimeout(5)->timeout(10)->get(self::BASE.'/proveedores/rubro', [
                'rpe' => $rpe,
                'limit' => 1,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $content = $response->json('payload.content');

        return is_array($content) && $content !== [];
    }

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
            try {
                $response = Http::timeout(10)->get(self::BASE.'/proveedores/rubro', [
                    'rpe' => $rpe,
                    'limit' => 100,
                    'page' => $page,
                ]);
            } catch (ConnectionException) {
                break;
            }

            if ($response->failed()) {
                break;
            }

            // An RPE the DGCP has no rubros for still answers 200, with
            // payload.content explicitly null. The key exists, so data_get
            // returns that null rather than the default — hence the cast.
            $content = $response->json('payload.content');

            foreach (is_array($content) ? $content : [] as $r) {
                if (! is_array($r)) {
                    continue;
                }

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
