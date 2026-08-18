<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Models\OfferParseAttempt;
use App\Services\GeminiService;
use App\Services\PortalScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Parse a pliego that lives only on the SECP portal (not the open-data API).
 * Downloads it server-side via the two-hop scraper, then hands the bytes to
 * Gemini — used when the offer's process documents come from the portal and
 * have a portal_file_id rather than a direct URL.
 */
class ParsePortalPliegoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(
        public Offer $offer,
        public string $noticeUid,
        public string $fileId,
        public string $filename,
        public ?int $attemptId = null,
        public ?int $userId = null,
    ) {
        $this->userId = $userId ?? auth()->id();
    }

    public function handle(GeminiService $gemini, PortalScraperService $scraper): void
    {
        $file = $scraper->downloadPortalDocument($this->noticeUid, $this->fileId);

        if (! $file || empty($file['body'])) {
            if ($this->attemptId) {
                OfferParseAttempt::whereKey($this->attemptId)->update([
                    'status' => 'failed',
                    'failure_reason' => 'No se pudo descargar el documento desde el portal DGCP.',
                ]);
            }

            return;
        }

        $gemini->parseFromContent($this->offer, $file['body'], $this->filename, $this->userId, $this->attemptId);
    }
}
