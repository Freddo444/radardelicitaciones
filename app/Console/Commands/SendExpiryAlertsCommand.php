<?php

namespace App\Console\Commands;

use App\Mail\DocumentExpiryMail;
use App\Models\Company;
use App\Models\Setting;
use App\Models\VaultDocument;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpiryAlertsCommand extends Command
{
    protected $signature = 'secp:send-expiry-alerts {--days=30 : Alert window in days} {--dry-run}';

    protected $description = 'Notify companies about vault documents expiring soon (email + Telegram, once per document)';

    public function handle(TelegramService $telegram): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $window = max(1, (int) $this->option('days'));

        $byCompany = VaultDocument::query()
            ->where('is_current', true)
            ->whereNotNull('expires_at')
            ->whereNull('expiry_notified_at')
            ->where('expires_at', '<=', now()->addDays($window))
            ->orderBy('expires_at')
            ->get()
            ->groupBy('company_id');

        $companiesNotified = 0;
        foreach ($byCompany as $companyId => $docs) {
            $company = Company::find($companyId);
            if (! $company) {
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY] company #{$companyId}: {$docs->count()} doc(s) expiring");

                continue;
            }

            $delivered = false;

            $recipient = Setting::get('notification_email', null, $companyId);
            if (is_string($recipient) && filter_var(trim($recipient), FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to(trim($recipient))->send(new DocumentExpiryMail($company, $docs));
                    $delivered = true;
                } catch (\Throwable $e) {
                    Log::error('[ExpiryAlerts] email failed', ['company_id' => $companyId, 'error' => $e->getMessage()]);
                }
            }

            if ($telegram->isConfigured($companyId)) {
                $lines = $docs->map(fn ($d) => "• {$d->name} — vence {$d->expires_at?->format('d/m/Y')}")->join("\n");
                $text = "📄 <b>Documentos por vencer ({$docs->count()})</b>\n\n{$lines}";
                try {
                    if ($telegram->sendMessage($text, $companyId)) {
                        $delivered = true;
                    }
                } catch (\Throwable $e) {
                    Log::error('[ExpiryAlerts] telegram failed', ['company_id' => $companyId, 'error' => $e->getMessage()]);
                }
            }

            // Only mark as notified once a channel actually delivered, so a
            // company that later configures email/Telegram still gets alerted.
            if ($delivered) {
                VaultDocument::whereIn('id', $docs->pluck('id'))->update(['expiry_notified_at' => now()]);
                $companiesNotified++;
            }
        }

        $this->info($dryRun
            ? "Dry run: {$byCompany->count()} company(ies) with expiring docs."
            : "Notified {$companiesNotified} company(ies) about expiring documents.");
        Log::info('[ExpiryAlerts] run complete', ['companies' => $byCompany->count(), 'notified' => $companiesNotified, 'dry_run' => $dryRun]);

        return self::SUCCESS;
    }
}
