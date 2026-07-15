<?php

namespace App\Http\Controllers;

use App\Support\ContactSpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function contact(Request $request, ContactSpamGuard $spamGuard)
    {
        // Bots get the same success response as humans so they can't tell
        // which heuristic caught them. Blocked attempts are logged.
        if ($spamGuard->isSpam($request)) {
            return back()->with('contact_sent', true);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $details = implode("\n", [
            "De: {$request->name} ({$request->email})",
            'Origen: Formulario de contacto (marketing)',
            '',
            $request->message,
        ]);

        try {
            Mail::raw($details, function ($msg) use ($request) {
                $msg->to(config('services.support.inbox'))
                    ->replyTo($request->email, $request->name)
                    ->subject("Contacto: {$request->name}");
            });
        } catch (\Throwable $e) {
            Log::error('support.contact_send_failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo enviar su mensaje en este momento. Intente de nuevo.');
        }

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

        try {
            Mail::raw($details, function ($msg) use ($request, $user, $screenshotPath) {
                $msg->to(config('services.support.inbox'))
                    ->replyTo($user->email, $user->name)
                    ->subject("Soporte: {$request->subject}");

                if ($screenshotPath) {
                    $msg->attach(storage_path("app/{$screenshotPath}"));
                }
            });
        } catch (\Throwable $e) {
            Log::error('support.ticket_send_failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo enviar el reporte ahora. Intente de nuevo.');
        }

        return back()->with('success', 'Reporte enviado. Te contactaremos pronto.');
    }
}
