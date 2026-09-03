<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\TrialClaim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeLapsedCompaniesCommand extends Command
{
    protected $signature = 'companies:purge-lapsed
        {--days=90 : Days a company must have been lapsed before its data is removed}
        {--dry-run : Report what would be removed without deleting anything}';

    protected $description = 'Remove data belonging to companies whose account lapsed, keeping billing records and a trial tombstone';

    /**
     * Tenant tables keyed by company_id. Deliberately excludes users,
     * subscriptions and payments: billing history has to survive for accounting,
     * and the user row anchors the trial tombstone.
     */
    private const TENANT_TABLES = [
        'company_bid', 'bid_documents', 'bid_watches', 'in_app_notifications',
        'notification_log', 'offer_parse_attempts', 'prellenado_packages',
        'rubros', 'personnel', 'projects', 'equipment', 'financial_records',
        'vault_documents', 'offers', 'settings', 'invitations',
        'google_calendar_tokens',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = max(30, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $entitled = Company::entitledIds();

        // Lapsed = not entitled, and the event that ended the account is older
        // than the retention window. Companies with no subscription at all are
        // left alone: that is an incomplete signup, not a lapsed customer.
        $candidates = Company::query()
            ->whereNotIn('id', $entitled)
            ->whereExists(function ($q) use ($cutoff) {
                $q->selectRaw('1')
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.user_id', 'companies.owner_id')
                    ->where(function ($q2) use ($cutoff) {
                        $q2->where(fn ($q3) => $q3->where('subscriptions.status', 'trialing')
                            ->whereNotNull('subscriptions.trial_ends_at')
                            ->where('subscriptions.trial_ends_at', '<', $cutoff))
                            ->orWhere(fn ($q3) => $q3->where('subscriptions.status', 'cancelled')
                                ->whereNotNull('subscriptions.cancelled_at')
                                ->where('subscriptions.cancelled_at', '<', $cutoff))
                            ->orWhere(fn ($q3) => $q3->where('subscriptions.status', 'suspended')
                                ->where('subscriptions.updated_at', '<', $cutoff))
                            ->orWhere(fn ($q3) => $q3->where('subscriptions.status', 'past_due')
                                ->whereNotNull('subscriptions.grace_ends_at')
                                ->where('subscriptions.grace_ends_at', '<', $cutoff));
                    });
            })
            ->get();

        if ($candidates->isEmpty()) {
            $this->info("No lapsed companies older than {$days} days.");

            return self::SUCCESS;
        }

        $purged = 0;
        $rowsTotal = 0;

        foreach ($candidates as $company) {
            $counts = $this->countRows($company->id);
            $rows = array_sum($counts);

            if ($dryRun) {
                $detail = collect($counts)->filter()->map(fn ($n, $t) => "{$t}={$n}")->join(' ');
                $this->line("[DRY] #{$company->id} {$company->razon_social} (RNC {$company->rnc}) → {$rows} row(s) ".($detail ?: '(sin datos)'));

                continue;
            }

            try {
                // Record the tombstone BEFORE deleting, so a purged business
                // cannot come back for a second free trial.
                TrialClaim::claim($company->rnc, $company->razon_social, $company->owner_id);
                TrialClaim::where('rnc', TrialClaim::normalize($company->rnc))
                    ->update(['purged_at' => now()]);

                $this->deleteFiles($company->id);

                DB::transaction(function () use ($company) {
                    foreach (self::TENANT_TABLES as $table) {
                        DB::table($table)->where('company_id', $company->id)->delete();
                    }
                    DB::table('company_user')->where('company_id', $company->id)->delete();
                    DB::table('companies')->where('id', $company->id)->delete();
                });

                $purged++;
                $rowsTotal += $rows;
                $this->info("Purgada #{$company->id} {$company->razon_social} — {$rows} fila(s)");
                Log::info('[PurgeLapsed] company purged', [
                    'company_id' => $company->id, 'rnc' => $company->rnc, 'rows' => $rows,
                ]);
            } catch (\Throwable $e) {
                $this->warn("FALLO #{$company->id}: {$e->getMessage()}");
                Log::error('[PurgeLapsed] purge failed', ['company_id' => $company->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info($dryRun
            ? "Dry run: {$candidates->count()} empresa(s) elegibles."
            : "Purgadas {$purged} empresa(s), {$rowsTotal} fila(s) liberada(s).");

        return self::SUCCESS;
    }

    /** @return array<string,int> */
    private function countRows(int $companyId): array
    {
        $counts = [];
        foreach (self::TENANT_TABLES as $table) {
            $counts[$table] = DB::table($table)->where('company_id', $companyId)->count();
        }

        return $counts;
    }

    /**
     * Both stores are laid out one directory per company: the vault at the disk
     * root, and pliegos under a repeated `bid_docs/` prefix (the writers include
     * it in the path they hand to a disk that is already rooted there).
     */
    private function deleteFiles(int $companyId): void
    {
        $dirs = [
            Storage::disk('vault')->path((string) $companyId),
            Storage::disk('bid_docs')->path("bid_docs/{$companyId}"),
            Storage::disk('bid_docs')->path((string) $companyId),
        ];

        foreach ($dirs as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }
    }
}
