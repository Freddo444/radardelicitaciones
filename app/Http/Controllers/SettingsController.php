<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DgcpApiClient;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index()
    {
        $cid = currentCompany()->id;

        $settings = [
            // Per-company settings
            'notification_email' => Setting::get('notification_email', null, $cid),
            'telegram_bot_token' => Setting::get('telegram_bot_token', null, $cid),
            'telegram_chat_id' => Setting::get('telegram_chat_id', null, $cid),
            'min_amount_filter' => Setting::get('min_amount_filter', '0', $cid),
            'min_amount_value' => Setting::get('min_amount_value', '0', $cid),
            'min_amount_currency' => Setting::get('min_amount_currency', 'DOP', $cid),
            'max_amount_filter' => Setting::get('max_amount_filter', '0', $cid),
            'max_amount_value' => Setting::get('max_amount_value', '0', $cid),
            'max_amount_currency' => Setting::get('max_amount_currency', 'DOP', $cid),
            'digest_enabled' => Setting::get('digest_enabled', '0', $cid),
            'digest_frequency' => Setting::get('digest_frequency', 'daily_9am', $cid),
            'open_deadline_filter' => Setting::get('open_deadline_filter', '0', $cid),
            'excluded_modalities' => json_decode(Setting::get('excluded_modalities', '[]', $cid), true) ?? [],
            'radar_keywords' => implode(', ', json_decode(Setting::get('radar_keywords', '[]', $cid), true) ?: []),
            'radar_excluded_keywords' => implode(', ', json_decode(Setting::get('radar_excluded_keywords', '[]', $cid), true) ?: []),
            // System-wide settings (read-only)
            'last_polled_at' => Setting::get('last_polled_at'),
            'catalog_item_count' => Setting::get('catalog_item_count'),
            'catalog_last_imported_at' => Setting::get('catalog_last_imported_at'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'notification_email' => 'nullable|email',
            'telegram_bot_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',

            'min_amount_value' => 'nullable|numeric|min:0',
            'min_amount_currency' => 'required|in:DOP,USD',
            'max_amount_value' => 'nullable|numeric|min:0',
            'max_amount_currency' => 'required|in:DOP,USD',
            'excluded_modalities' => 'nullable|array',
            'excluded_modalities.*' => 'string',
        ]);

        $cid = currentCompany()->id;

        // Per-company settings
        Setting::set('digest_enabled', $request->has('digest_enabled') ? '1' : '0', $cid);
        Setting::set('digest_frequency', $request->input('digest_frequency', 'daily_9am'), $cid);
        Setting::set('notification_email', $request->notification_email, $cid);
        Setting::set('telegram_bot_token', $request->telegram_bot_token, $cid);
        Setting::set('telegram_chat_id', $request->telegram_chat_id, $cid);
        // Per-company filters
        $minVal = $request->min_amount_value ?? '0';
        Setting::set('min_amount_filter', ((float) $minVal > 0) ? '1' : '0', $cid);
        Setting::set('min_amount_value', $minVal, $cid);
        Setting::set('min_amount_currency', $request->min_amount_currency, $cid);
        $maxVal = $request->max_amount_value ?? '0';
        Setting::set('max_amount_filter', ((float) $maxVal > 0) ? '1' : '0', $cid);
        Setting::set('max_amount_value', $maxVal, $cid);
        Setting::set('max_amount_currency', $request->max_amount_currency, $cid);
        Setting::set('open_deadline_filter', $request->has('open_deadline_filter') ? '1' : '0', $cid);
        Setting::set('excluded_modalities', json_encode($request->input('excluded_modalities', [])), $cid);

        // Keyword radar (per-company)
        $positiveRaw = $request->input('radar_keywords', '');
        $negativeRaw = $request->input('radar_excluded_keywords', '');
        Setting::set('radar_keywords', json_encode(
            array_values(array_filter(array_map('trim', explode(',', $positiveRaw))))
        ), $cid);
        Setting::set('radar_excluded_keywords', json_encode(
            array_values(array_filter(array_map('trim', explode(',', $negativeRaw))))
        ), $cid);

        return redirect()->route('settings.index')->with('success', 'Configuración guardada correctamente.');
    }

    public function importCatalog()
    {
        \Artisan::queue('secp:import-catalog');

        return back()->with('success', 'Importación del catálogo iniciada en segundo plano.');
    }

    public function testConnection(DgcpApiClient $api)
    {
        $ok = $api->testConnection();

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? 'Conexión con API DGCP exitosa.' : 'No se pudo conectar con la API DGCP.'
        );
    }

    public function testEmail()
    {
        $recipient = Setting::get('notification_email', null, currentCompany()->id);

        if (empty($recipient)) {
            return back()->with('error', 'Configure un correo de destino primero.');
        }

        try {
            Mail::raw('Prueba de notificación de Radar de Licitaciones. Si recibes esto, el correo está configurado correctamente.', function ($msg) use ($recipient) {
                $msg->to($recipient)->subject('Correo de prueba — Radar de Licitaciones');
            });

            return back()->with('success', "Correo de prueba enviado a {$recipient}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al enviar correo: '.$e->getMessage());
        }
    }

    public function testTelegram(TelegramService $telegram)
    {
        if (! $telegram->isConfigured()) {
            return back()->with('error', 'Configure el token del bot y el Chat ID primero.');
        }

        $sent = $telegram->sendMessage('Radar de Licitaciones — Prueba de notificación exitosa.');

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'Mensaje de Telegram enviado correctamente.' : 'Error al enviar mensaje de Telegram. Verifique el token y Chat ID.'
        );
    }
}
