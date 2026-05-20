<?php

use App\View\Components\UmamiTrack;

if (! function_exists('umami_flash_payload')) {
    /**
     * Build session payload for {@see UmamiTrack} on the next request.
     *
     * Auto-merges UTM attribution captured by CaptureUtmAttributionMiddleware
     * so every event carries its campaign context.
     *
     * @return array{event: string, data: array<string, mixed>}|null
     */
    function umami_flash_payload(string $event, array $data = []): ?array
    {
        if (! config('analytics.umami.enabled')) {
            return null;
        }

        $attribution = session('attribution', []);
        if (is_array($attribution) && ! empty($attribution)) {
            // Caller-supplied data wins over attribution on key collision
            $data = array_merge($attribution, $data);
        }

        return [
            'event' => $event,
            'data' => $data,
        ];
    }
}
