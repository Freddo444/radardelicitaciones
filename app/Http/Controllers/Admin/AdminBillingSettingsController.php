<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Billing\BpdRateClient;
use App\Services\Billing\UsdDopExchange;
use Illuminate\Http\Request;

class AdminBillingSettingsController extends Controller
{
    public function edit()
    {
        $stored = Setting::get(UsdDopExchange::SETTING_KEY, null, null);

        return view('admin.billing-settings', [
            'stored_rate' => ($stored !== null && $stored !== '' && is_numeric($stored)) ? (string) $stored : null,
            'effective_rate' => UsdDopExchange::rate(),
            'config_fallback' => UsdDopExchange::configFallback(),
            'mode' => UsdDopExchange::isManualMode() ? 'manual' : 'auto',
            'source' => UsdDopExchange::source(),
            'auto_rate' => UsdDopExchange::autoRate(),
            'auto_fetched_at' => UsdDopExchange::autoFetchedAt(),
            'auto_is_stale' => UsdDopExchange::autoRateIsStale(),
            'bpd_configured' => app(BpdRateClient::class)->isConfigured(),
            'band' => UsdDopExchange::plausibleBand(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:auto,manual',
            // Still required in auto mode: it is the value that covers the gap
            // before the first successful fetch, and the one automation falls
            // back to if the bank cannot be reached.
            'usd_dop_rate' => 'required|numeric|min:1|max:999.99',
        ]);

        $rate = round((float) $request->usd_dop_rate, 4);

        Setting::set(UsdDopExchange::SETTING_KEY, (string) $rate, null);
        Setting::set(UsdDopExchange::MODE_SETTING_KEY, $request->input('mode'), null);

        $message = $request->input('mode') === 'manual'
            ? 'Modo manual: el cobro usa 1 USD = '.$rate.' DOP y se ignora el Banco Popular.'
            : 'Modo automático: el cobro usa la tasa del Banco Popular, con '.$rate.' DOP como respaldo.';

        return redirect()->route('admin.billing-settings.edit')->with('success', $message);
    }
}
