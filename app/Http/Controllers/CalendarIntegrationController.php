<?php

namespace App\Http\Controllers;

use App\Jobs\SyncTableroGoogleCalendarJob;
use App\Models\GoogleCalendarToken;
use App\Services\Calendar\GoogleCalendarSyncService;
use Google\Client as GoogleClient;
use Google\Service\Oauth2;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CalendarIntegrationController extends Controller
{
    public function regenerateFeedToken(): RedirectResponse
    {
        $company = currentCompany();
        $company->forceFill(['calendar_feed_token' => bin2hex(random_bytes(32))])->save();

        return redirect()->to(route('settings.index').'#calendario')
            ->with('success', 'Se generó un nuevo enlace iCalendar. Actualice la suscripción en Google Calendar, Outlook u otros clientes si ya estaba suscrito.');
    }

    public function googleRedirect(Request $request): RedirectResponse
    {
        if (! config('services.google_calendar.client_id') || ! config('services.google_calendar.client_secret')) {
            return redirect()->to(route('settings.index').'#calendario')
                ->with('error', 'La integración con Google Calendar no está configurada en el servidor.');
        }

        $state = Str::random(40);
        Cache::put($this->oauthCacheKey($state), [
            'user_id' => (int) $request->user()->id,
            'company_id' => (int) currentCompany()->id,
        ], now()->addMinutes(10));

        $client = $this->newGoogleClient();
        $client->setState($state);

        return redirect()->away($client->createAuthUrl());
    }

    public function googleCallback(Request $request, GoogleCalendarSyncService $sync): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $payload = Cache::pull($this->oauthCacheKey($state));
        if (! $payload || (int) $payload['user_id'] !== (int) $request->user()->id) {
            abort(403);
        }

        $company = currentCompany();
        if ((int) $payload['company_id'] !== (int) $company->id) {
            abort(403);
        }

        if ($request->filled('error')) {
            return redirect()->to(route('settings.index').'#calendario')
                ->with('error', 'No se pudo conectar con Google.');
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect()->to(route('settings.index').'#calendario')
                ->with('error', 'Respuesta incompleta de Google.');
        }

        $client = $this->newGoogleClient();
        try {
            $tokenResponse = $client->fetchAccessTokenWithAuthCode($code);
        } catch (\Throwable) {
            return redirect()->to(route('settings.index').'#calendario')
                ->with('error', 'No se pudo validar la autorización con Google.');
        }

        if (! is_array($tokenResponse) || isset($tokenResponse['error'])) {
            return redirect()->to(route('settings.index').'#calendario')
                ->with('error', 'Google rechazó la autorización.');
        }

        $client->setAccessToken($tokenResponse);

        $googleEmail = null;
        try {
            $oauth = new Oauth2($client);
            $googleEmail = $oauth->userinfo->get()->email;
        } catch (\Throwable) {
            //
        }

        $existing = GoogleCalendarToken::query()
            ->where('user_id', $request->user()->id)
            ->where('company_id', $company->id)
            ->first();

        $refreshToken = $tokenResponse['refresh_token'] ?? $existing?->refresh_token;

        $record = GoogleCalendarToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'company_id' => $company->id,
            ],
            [
                'access_token' => $tokenResponse['access_token'],
                'refresh_token' => $refreshToken,
                'expires_at' => isset($tokenResponse['expires_in'])
                    ? now()->addSeconds((int) $tokenResponse['expires_in'])
                    : now()->addHour(),
                'scope' => $tokenResponse['scope'] ?? null,
                'google_email' => $googleEmail,
                'calendar_id' => $existing?->calendar_id ?: 'primary',
                'sync_enabled' => true,
                'last_sync_error' => null,
            ]
        );

        SyncTableroGoogleCalendarJob::dispatch($company->id, 'full_resync', ['token_id' => $record->id]);

        return redirect()->to(route('settings.index').'#calendario')
            ->with('success', 'Google Calendar conectado. Estamos sincronizando el tablero en segundo plano.');
    }

    public function googleDisconnect(GoogleCalendarSyncService $sync): RedirectResponse
    {
        $token = GoogleCalendarToken::query()
            ->where('user_id', Auth::id())
            ->where('company_id', currentCompany()->id)
            ->first();

        if ($token) {
            $sync->disconnect($token);
        }

        return redirect()->to(route('settings.index').'#calendario')
            ->with('success', 'Google Calendar desconectado.');
    }

    public function googleToggleSync(Request $request): RedirectResponse
    {
        $token = GoogleCalendarToken::query()
            ->where('user_id', $request->user()->id)
            ->where('company_id', currentCompany()->id)
            ->firstOrFail();

        $enabled = ! $token->sync_enabled;
        $token->update(['sync_enabled' => $enabled]);

        if ($enabled) {
            SyncTableroGoogleCalendarJob::dispatch(currentCompany()->id, 'full_resync', ['token_id' => $token->id]);
        }

        return redirect()->to(route('settings.index').'#calendario')
            ->with('success', $enabled ? 'Sincronización activada.' : 'Sincronización pausada.');
    }

    public function googleResync(Request $request): RedirectResponse
    {
        $token = GoogleCalendarToken::query()
            ->where('user_id', $request->user()->id)
            ->where('company_id', currentCompany()->id)
            ->firstOrFail();

        SyncTableroGoogleCalendarJob::dispatch(currentCompany()->id, 'full_resync', ['token_id' => $token->id]);

        return redirect()->to(route('settings.index').'#calendario')
            ->with('success', 'Sincronización completa encolada.');
    }

    private function newGoogleClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId((string) config('services.google_calendar.client_id'));
        $client->setClientSecret((string) config('services.google_calendar.client_secret'));
        $client->setRedirectUri(route('settings.calendar.google.callback', [], true));
        $client->setScopes([
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/userinfo.email',
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    private function oauthCacheKey(string $state): string
    {
        return 'google_cal_oauth:'.$state;
    }
}
