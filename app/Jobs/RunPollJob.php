<?php

namespace App\Jobs;

use App\Models\CompanyBid;
use App\Models\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunPollJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour — large clients with many familias can take 20-40 min

    public function __construct(
        public ?int $triggeredByUserId = null,
        public ?int $triggeredByCompanyId = null,
    ) {}

    public function handle(): void
    {
        $beforeCount = $this->triggeredByCompanyId !== null
            ? CompanyBid::where('company_id', $this->triggeredByCompanyId)->count()
            : 0;

        Artisan::call('secp:poll');

        if ($this->triggeredByUserId === null || $this->triggeredByCompanyId === null) {
            return;
        }

        try {
            $afterCount = CompanyBid::where('company_id', $this->triggeredByCompanyId)->count();
            $newCount = max(0, $afterCount - $beforeCount);

            InAppNotification::create([
                'company_id' => $this->triggeredByCompanyId,
                'user_id' => $this->triggeredByUserId,
                'type' => 'poll_complete',
                'title' => 'Sondeo concluido',
                'body' => $newCount > 0
                    ? "Se vincularon {$newCount} nueva(s) propuesta(s) a su empresa."
                    : 'No se encontraron nuevas coincidencias.',
                'data' => ['new_count' => $newCount],
            ]);
        } catch (\Throwable $e) {
            Log::error('[RunPollJob] Failed to create completion notification', [
                'user_id' => $this->triggeredByUserId,
                'company_id' => $this->triggeredByCompanyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
