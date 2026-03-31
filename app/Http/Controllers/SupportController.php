<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function contact(Request $request)
    {
        if ($request->filled('website')) {
            return back()->with('contact_sent', true);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $details = implode("\n", [
            "De: {$request->name} ({$request->email})",
            "Origen: Formulario de contacto (marketing)",
            '',
            $request->message,
        ]);

        Mail::raw($details, function ($msg) use ($request) {
            $msg->to('support@radardelicitaciones.com')
                ->replyTo($request->email, $request->name)
                ->subject("Contacto: {$request->name}");
        });

        return back()->with('contact_sent', true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'screenshot' => 'nullable|image|max:5120',
        ]);

        $user = Auth::user();
        $company = currentCompany();
        $screenshotPath = null;

        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('support', 'local');
        }

        $details = implode("\n", [
            "De: {$user->name} ({$user->email})",
            'Empresa: '.($company?->razon_social ?? 'Sin empresa'),
            "URL: {$request->header('Referer', 'N/D')}",
            "User-Agent: {$request->userAgent()}",
            '',
            $request->body,
        ]);

        Mail::raw($details, function ($msg) use ($request, $user, $screenshotPath) {
            $msg->to('support@radardelicitaciones.com')
                ->replyTo($user->email, $user->name)
                ->subject("Soporte: {$request->subject}");

            if ($screenshotPath) {
                $msg->attach(storage_path("app/{$screenshotPath}"));
            }
        });

        return back()->with('success', 'Reporte enviado. Te contactaremos pronto.');
    }
}
