<?php

namespace App\Http\Controllers;

use App\Jobs\RunPollJob;
use App\Models\Setting;
use App\Services\BidMatchingService;

class PollController extends Controller
{
    public function manual()
    {
        if (Setting::get('poll_status') === 'running') {
            return back()->with('success', 'Ya hay un sondeo en ejecución. Le notificaremos cuando concluya.');
        }

        Setting::set('poll_status', 'running');
        Setting::set('poll_log', '[]');
        Setting::set('poll_started_at', now()->toDateTimeString());

        $user = auth()->user();
        $company = currentCompany();

        RunPollJob::dispatch($user?->id, $company?->id);

        return back()->with('success', 'Sondeo iniciado. Le notificaremos cuando concluya.');
    }

    public function sondear(BidMatchingService $matcher)
    {
        $companyId = currentCompany()->id;
        $matched = $matcher->sondear($companyId);

        $msg = $matched > 0
            ? "{$matched} convocatoria(s) nueva(s) vinculada(s) a su empresa."
            : 'No se encontraron nuevas coincidencias.';

        return back()->with($matched > 0 ? 'success' : 'info', $msg);
    }
}
