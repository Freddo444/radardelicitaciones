<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Billing\BpdRateClient;
use App\Services\Billing\UsdDopExchange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Stores the current USD/DOP rate from Banco Popular for the checkout path to
 * read. Runs on a schedule; checkout never calls the bank itself, because
 * ConsultaTasa is on the bank's Premium plan with undocumented rate limits and
 * a card payment must not depend on a third party answering.
 */
class RefreshUsdDopRateCommand extends Command
{
    protected $signature = 'billing:refresh-usd-rate
        {--dry-run : Read and report the rate without storing it}';

    protected $description = 'Read the published USD/DOP rate from Banco Popular and store it for billing';

    public function handle(BpdRateClient $client): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $client->isConfigured()) {
            $this->error('Banco Popular no está configurado. Define BPD_BASE_URL, BPD_CLIENT_ID y BPD_CLIENT_SECRET.');

            return self::FAILURE;
        }

        $rates = $client->rates();

        if ($rates === null) {
            $this->error('No se pudo leer la tasa del Banco Popular. El cobro sigue usando la tasa almacenada.');
            // BpdRateClient has already logged the specific cause at error
            // level; this line is what makes the scheduled run itself visible.
            Log::error('[BPD] Rate refresh produced nothing; billing continues on the stored rate.', [
                'effective_rate' => UsdDopExchange::rate(),
                'source' => UsdDopExchange::source(),
            ]);

            return self::FAILURE;
        }

        foreach ($rates as $code => $pair) {
            $this->line(sprintf('  %s  compra=%s  venta=%s', $code,
                $pair['compra'] !== null ? number_format($pair['compra'], 4) : '—',
                $pair['venta'] !== null ? number_format($pair['venta'], 4) : '—'));
        }

        // venta, not compra: we price in USD and charge in DOP, so the rate
        // that keeps us whole is the one the bank sells dollars at — the
        // higher of the two. Charging at compra would quietly discount every
        // sale by the spread.
        $side = (string) config('services.bpd.rate_side', 'venta');
        $rate = $rates['USD'][$side] ?? null;

        if ($rate === null) {
            $this->error("La respuesta no trae un valor '{$side}' usable para USD.");
            Log::warning('[BPD] USD rate missing from response', ['side' => $side, 'rates' => $rates]);

            return self::FAILURE;
        }

        if (! UsdDopExchange::isPlausible($rate)) {
            [$min, $max] = UsdDopExchange::plausibleBand();
            $this->error(sprintf(
                'Tasa fuera de rango: %s (se esperaba entre %s y %s). No se guardó.',
                number_format($rate, 4), number_format($min, 2), number_format($max, 2)
            ));
            Log::error('[BPD] Implausible rate refused', ['rate' => $rate, 'min' => $min, 'max' => $max]);

            return self::FAILURE;
        }

        $previous = UsdDopExchange::autoRate();

        if ($dryRun) {
            $this->info(sprintf('[DRY] USD %s = %s DOP (anterior: %s)', $side,
                number_format($rate, 4),
                $previous !== null ? number_format($previous, 4) : 'ninguna'));

            return self::SUCCESS;
        }

        Setting::set(UsdDopExchange::AUTO_SETTING_KEY, (string) round($rate, 4), null);
        Setting::set(UsdDopExchange::AUTO_AT_SETTING_KEY, now()->toDateTimeString(), null);

        $this->info(sprintf('Tasa guardada: 1 USD = %s DOP (%s).', number_format($rate, 4), $side));

        if ($previous !== null && $previous > 0) {
            $move = (($rate - $previous) / $previous) * 100;
            $this->line(sprintf('  Cambio frente a la anterior (%s): %+.2f%%', number_format($previous, 4), $move));
        }

        if (UsdDopExchange::isManualMode()) {
            $this->warn('  El modo está en MANUAL, así que el cobro sigue usando la tasa manual y no esta. Cámbialo en Admin → Facturación.');
        }

        Log::info('[BPD] USD/DOP rate stored', ['rate' => $rate, 'side' => $side, 'previous' => $previous]);

        return self::SUCCESS;
    }
}
