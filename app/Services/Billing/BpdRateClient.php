<?php

namespace App\Services\Billing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the published DOP exchange rates from Banco Popular's ConsultaTasa API.
 *
 * The bank's technical reference is behind a portal login, so the bundled
 * Swagger file (My-Plans/api/BPDConsultaTasa-2.6.1.json) is the only
 * authoritative description available. Two consequences are deliberate here:
 *
 * - The base URL is configuration with no production default. The bank does
 *   not publish the production host; it is sent by email after an approval
 *   process, and the portal warns that URLs are catalogue-specific. Guessing a
 *   "bpdprod" variant would be inventing an endpoint.
 * - The token request is tried as a form body first and then as HTTP Basic.
 *   The spec says OAuth2 "application" flow (client_credentials) but does not
 *   say where the credentials go, and RFC 6749 permits both.
 *
 * Nothing here is called during checkout. ConsultaTasa sits on the bank's
 * Premium plan with undocumented rate limits, so a scheduled command refreshes
 * a stored value and the checkout path only ever reads that.
 */
class BpdRateClient
{
    private const TOKEN_CACHE_KEY = 'bpd:oauth_token';

    public function __construct(
        private ?string $baseUrl = null,
        private ?string $clientId = null,
        private ?string $clientSecret = null,
    ) {
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('services.bpd.base_url')), '/');
        $this->clientId = $clientId ?? config('services.bpd.client_id');
        $this->clientSecret = $clientSecret ?? config('services.bpd.client_secret');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->clientId && $this->clientSecret;
    }

    /**
     * Published rates keyed by currency, e.g. ['USD' => ['compra' => 61.5,
     * 'venta' => 63.0]]. Null means we could not read them — never an
     * assumed rate.
     *
     * @return array<string, array{compra: float|null, venta: float|null}>|null
     */
    public function rates(): ?array
    {
        if (! $this->isConfigured()) {
            Log::error('[BPD] Skipped rate lookup: credentials or base URL not configured.');

            return null;
        }

        $token = $this->accessToken();

        if ($token === null) {
            return null;
        }

        try {
            $response = Http::connectTimeout(10)
                ->timeout(20)
                ->withHeaders([
                    'X-IBM-Client-Id' => $this->clientId,
                    'Accept' => 'application/json',
                ])
                ->withToken($token)
                ->get($this->baseUrl.'/consultaTasa');
        } catch (ConnectionException $e) {
            Log::error('[BPD] consultaTasa unreachable', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->status() === 401) {
            // The cached token may have been revoked before its stated expiry.
            Cache::forget(self::TOKEN_CACHE_KEY);
            Log::error('[BPD] consultaTasa rejected the token; cache cleared for the next run.');

            return null;
        }

        if ($response->failed()) {
            Log::error('[BPD] consultaTasa failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return null;
        }

        return $this->parseRates($response->json());
    }

    /**
     * @return array<string, array{compra: float|null, venta: float|null}>|null
     */
    private function parseRates(mixed $payload): ?array
    {
        $rows = data_get($payload, 'monedas.moneda');

        if (! is_array($rows) || $rows === []) {
            Log::error('[BPD] consultaTasa returned no currencies', [
                'payload' => mb_substr(json_encode($payload) ?: '', 0, 300),
            ]);

            return null;
        }

        $rates = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['descripcion'] ?? '')));

            if ($code === '') {
                continue;
            }

            $rates[$code] = [
                'compra' => $this->toRate($row['compra'] ?? null),
                'venta' => $this->toRate($row['venta'] ?? null),
            ];
        }

        return $rates === [] ? null : $rates;
    }

    /**
     * The spec declares compra/venta as integers but gives 56.95 as an example,
     * so it contradicts itself and the portal cannot settle it. Accept a JSON
     * number or a numeric string, and reject anything non-finite or
     * non-positive rather than letting it reach a price.
     */
    private function toRate(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(',', '', trim($value));
        }

        if (! is_numeric($value)) {
            return null;
        }

        $rate = (float) $value;

        return (is_finite($rate) && $rate > 0) ? $rate : null;
    }

    /**
     * The OAuth token endpoint.
     *
     * It sits at the catalogue root, one level ABOVE the API's own base path:
     *   https://host/apiportalpopular/bpdsandbox/bpd/Authentication/oauth2/token
     * not
     *   https://host/apiportalpopular/bpdsandbox/consultatasa/bpd/Authentication/...
     *
     * The spec says this twice and inconsistently: securityDefinitions gives a
     * relative "/bpd/Authentication/oauth2/token", which reads as though it
     * hangs off basePath, while x-ibm-configuration.oauth-servers gives the
     * absolute URL without the consultatasa segment. The absolute one is
     * right; resolving the relative one against basePath returns 404
     * "No resources match requested URI".
     *
     * Deriving it by dropping the last segment means a production base URL
     * works without a second environment variable, and BPD_TOKEN_URL remains
     * available if the bank's production catalogue is laid out differently.
     */
    private function tokenUrl(): string
    {
        $explicit = trim((string) config('services.bpd.token_url'));

        if ($explicit !== '') {
            return rtrim($explicit, '/');
        }

        $catalogue = preg_replace('~/[^/]+$~', '', $this->baseUrl);

        return $catalogue.'/bpd/Authentication/oauth2/token';
    }

    private function accessToken(): ?string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $url = $this->tokenUrl();
        $scope = (string) config('services.bpd.scope');

        $form = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if ($scope !== '') {
            $form['scope'] = $scope;
        }

        // Credentials in the body first, then Basic. The spec names the flow
        // but not the credential placement, and the portal's reference is
        // login-gated, so this resolves it at runtime instead of by guesswork.
        $attempts = [
            fn () => Http::asForm()->connectTimeout(10)->timeout(20)->post($url, $form),
            fn () => Http::asForm()
                ->withBasicAuth((string) $this->clientId, (string) $this->clientSecret)
                ->connectTimeout(10)
                ->timeout(20)
                ->post($url, array_diff_key($form, ['client_id' => '', 'client_secret' => ''])),
        ];

        $lastFailure = null;
        $lastStatus = null;
        $lastBody = null;

        foreach ($attempts as $i => $attempt) {
            try {
                $response = $attempt();
            } catch (ConnectionException $e) {
                Log::error('[BPD] Token endpoint unreachable', ['error' => $e->getMessage()]);

                return null;
            }

            if ($response->failed()) {
                // Per-attempt detail stays at warning: the first rejection is
                // expected when the credentials belong in a Basic header. The
                // exhausted case below is an error, because production runs at
                // LOG_LEVEL=error and a silent failure here means billing
                // quietly keeps using a stale rate.
                Log::warning('[BPD] Token request rejected', [
                    'style' => $i === 0 ? 'form-body' : 'basic-auth',
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 200),
                ]);
                $lastFailure = $i === 0 ? 'form-body' : 'basic-auth';
                $lastStatus = $response->status();
                $lastBody = mb_substr($response->body(), 0, 200);

                continue;
            }

            $token = (string) ($response->json('access_token') ?? '');

            if ($token === '') {
                Log::warning('[BPD] Token response carried no access_token.');

                continue;
            }

            // Expire a minute early so a token is never used on the boundary.
            $ttl = max(60, (int) ($response->json('expires_in') ?? 3600) - 60);
            Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

            return $token;
        }

        Log::error('[BPD] Could not obtain an access token', [
            'last_style' => $lastFailure,
            'status' => $lastStatus,
            'body' => $lastBody,
            'token_url' => $this->tokenUrl(),
        ]);

        return null;
    }
}
