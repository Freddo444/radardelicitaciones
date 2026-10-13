<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\DgcpProviderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Records which companies' RNCs appear in the DGCP supplier registry.
 *
 * Only ever sets the flag, never clears it. The registry lags a newly
 * incorporated business by up to a couple of months, so an absent RNC means
 * "not listed yet", not "not real" — which is also why this is worth running
 * again later: a company unverified today may be registered in two months.
 */
class VerifyCompanyRncCommand extends Command
{
    protected $signature = 'companies:verify-rnc
        {--dry-run : Report what would change without writing}
        {--recheck : Query again for companies already marked verified}';

    protected $description = 'Check each company RNC against the DGCP supplier registry and flag the ones found';

    public function handle(DgcpProviderService $dgcp): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $companies = Company::withoutGlobalScopes()
            ->when(! $this->option('recheck'), fn ($q) => $q->whereNull('rnc_verified_at'))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->info('Nothing to check.');

            return self::SUCCESS;
        }

        $verified = 0;
        $unlisted = 0;
        $unknown = 0;

        foreach ($companies as $company) {
            $result = $dgcp->isRegistered($company->rnc);
            $label = sprintf('#%-3d %-12s %s', $company->id, $company->rnc, mb_substr((string) $company->razon_social, 0, 32));

            if ($result === true) {
                $verified++;
                $this->info(($dryRun ? '[DRY] ' : '').'REGISTRADO    '.$label);

                if (! $dryRun) {
                    $company->forceFill(['rnc_verified_at' => now()])->save();
                }

                continue;
            }

            if ($result === false) {
                $unlisted++;
                // Left null deliberately — see the class comment.
                $this->line('  no listado  '.$label);

                continue;
            }

            $unknown++;
            $this->warn('  sin respuesta '.$label.'  (RNC mal formado o DGCP no disponible)');
        }

        $summary = sprintf(
            '%s %d registrado(s), %d no listado(s), %d sin verificar.',
            $dryRun ? 'Dry run:' : 'RNC:',
            $verified,
            $unlisted,
            $unknown
        );

        $this->info($summary);

        if (! $dryRun) {
            Log::info('[VerifyRnc] '.$summary);
        }

        return self::SUCCESS;
    }
}
