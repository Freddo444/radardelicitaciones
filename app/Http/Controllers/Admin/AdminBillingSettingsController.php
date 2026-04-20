<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Billing\UsdDopExchange;
use Illuminate\Http\Request;

class AdminBillingSettingsController extends Controller
{
    public function edit()
    {
        $stored = Setting::get(UsdDopExchange::SETTING_KEY, null, null);
        $effectiveRate = UsdDopExchange::rate();

        return view('admin.billing-settings', [
            'stored_rate' => ($stored !== null && $stored !== '' && is_numeric($stored)) ? (string) $stored : null,
            'effective_rate' => $effectiveRate,
            'config_fallback' => UsdDopExchange::configFallback(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'usd_dop_rate' => 'required|numeric|min:1|max:999.99',
        ]);

        $rate = round((float) $request->usd_dop_rate, 4);
        Setting::set(UsdDopExchange::SETTING_KEY, (string) $rate, null);

        return redirect()->route('admin.billing-settings.edit')
            ->with('success', 'Tipo de cambio actualizado: 1 USD = '.$rate.' DOP.');
    }
}
