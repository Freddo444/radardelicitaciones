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
            return redirect()->route('poll.progress');
        }

        Setting::set('poll_status', 'running');
        Setting::set('poll_log', '[]');
        Setting::set('poll_started_at', now()->toDateTimeString());

        RunPollJob::dispatch();

        return redirect()->route('poll.progress');
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
