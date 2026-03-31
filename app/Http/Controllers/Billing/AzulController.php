<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AzulController extends Controller
{
    // TODO: Implement once Azul credentials are provided

    public function createPayment()
    {
        return back()->with('info', 'Pagos con Azul estaran disponibles proximamente.');
    }

    public function handleCallback(Request $request)
    {
        return redirect()->route('billing.index')
            ->with('info', 'Azul: integracion pendiente.');
    }

    public function handleWebhook(Request $request)
    {
        return response('OK', 200);
    }
}
