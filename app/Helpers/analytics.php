<?php

if (! function_exists('umami_flash_payload')) {
    /**
     * Build session payload for {@see \App\View\Components\UmamiTrack} on the next request.
     *
     * @return array{event: string, data: array<string, mixed>}|null
     */
    function umami_flash_payload(string $event, array $data = []): ?array
    {
        if (! config('analytics.umami.enabled')) {
            return null;
        }

        return [
            'event' => $event,
            'data' => $data,
        ];
    }
}
