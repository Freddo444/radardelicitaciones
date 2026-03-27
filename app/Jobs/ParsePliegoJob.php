<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParsePliegoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 1;

    public function __construct(
        public Offer $offer,
        public string $pdfUrl,
        public string $filename,
        public ?int $userId = null,
    ) {
        $this->userId = $userId ?? auth()->id();
    }

    public function handle(GeminiService $gemini): void
    {
        $gemini->fetchAndParse($this->offer, $this->pdfUrl, $this->filename, $this->userId);
    }
}
