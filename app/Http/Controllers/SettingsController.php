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
        $settings = [
            'notification_email' => Setting::get('notification_email'),
            'telegram_bot_token' => Setting::get('telegram_bot_token'),
            'telegram_chat_id' => Setting::get('telegram_chat_id'),
            'poll_interval_minutes' => Setting::get('poll_interval_minutes', 60),
            'last_polled_at' => Setting::get('last_polled_at'),
            'min_amount_filter' => Setting::get('min_amount_filter', '0'),
            'min_amount_value' => Setting::get('min_amount_value', '0'),
            'min_amount_currency' => Setting::get('min_amount_currency', 'DOP'),
            'max_amount_filter' => Setting::get('max_amount_filter', '0'),
            'max_amount_value' => Setting::get('max_amount_value', '0'),
            'max_amount_currency' => Setting::get('max_amount_currency', 'DOP'),
            'notification_mode' => Setting::get('notification_mode', 'instant'),
            'digest_frequency' => Setting::get('digest_frequency', 'daily_9am'),
            'catalog_item_count' => Setting::get('catalog_item_count'),
            'catalog_last_imported_at' => Setting::get('catalog_last_imported_at'),
            'open_deadline_filter' => Setting::get('open_deadline_filter', '0'),
            'excluded_modalities' => json_decode(Setting::get('excluded_modalities', '[]'), true) ?? [],
            'radar_keywords' => implode(', ', json_decode(Setting::get('radar_keywords', '[]'), true) ?: []),
            'radar_excluded_keywords' => implode(', ', json_decode(Setting::get('radar_excluded_keywords', '[]'), true) ?: []),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'notification_email' => 'nullable|email',
            'telegram_bot_token' => 'nullable|string',
            'telegram_chat_id' => 'nullable|string',
            'poll_interval_minutes' => 'required|integer|min:10|max:1440',
            'min_amount_value' => 'nullable|numeric|min:0',
            'min_amount_currency' => 'required|in:DOP,USD',
            'max_amount_value' => 'nullable|numeric|min:0',
            'max_amount_currency' => 'required|in:DOP,USD',
            'excluded_modalities' => 'nullable|array',
            'excluded_modalities.*' => 'string',
        ]);

        Setting::set('notification_mode', $request->input('notification_mode', 'instant'));
        Setting::set('digest_frequency', $request->input('digest_frequency', 'daily_9am'));
        Setting::set('notification_email', $request->notification_email);
        Setting::set('telegram_bot_token', $request->telegram_bot_token);
        Setting::set('telegram_chat_id', $request->telegram_chat_id);
        Setting::set('poll_interval_minutes', $request->poll_interval_minutes);
        Setting::set('min_amount_filter', $request->has('min_amount_filter') ? '1' : '0');
        Setting::set('min_amount_value', $request->min_amount_value ?? '0');
        Setting::set('min_amount_currency', $request->min_amount_currency);
        Setting::set('max_amount_filter', $request->has('max_amount_filter') ? '1' : '0');
        Setting::set('max_amount_value', $request->max_amount_value ?? '0');
        Setting::set('max_amount_currency', $request->max_amount_currency);
        Setting::set('open_deadline_filter', $request->has('open_deadline_filter') ? '1' : '0');
        Setting::set('excluded_modalities', json_encode($request->input('excluded_modalities', [])));

        // Keyword radar
        $positiveRaw = $request->input('radar_keywords', '');
        $negativeRaw = $request->input('radar_excluded_keywords', '');
        Setting::set('radar_keywords', json_encode(
            array_values(array_filter(array_map('trim', explode(',', $positiveRaw))))
        ));
        Setting::set('radar_excluded_keywords', json_encode(
            array_values(array_filter(array_map('trim', explode(',', $negativeRaw))))
        ));

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
        $recipient = Setting::get('notification_email');

        if (empty($recipient)) {
            return back()->with('error', 'Configure un correo de destino primero.');
        }

        try {
            Mail::raw('Prueba de notificación del Monitor SECP. Si recibes esto, el correo está configurado correctamente.', function ($msg) use ($recipient) {
                $msg->to($recipient)->subject('[SECP] Correo de prueba');
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

        $sent = $telegram->sendMessage('🔔 <b>Monitor SECP</b> — Prueba de notificación exitosa.');

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'Mensaje de Telegram enviado correctamente.' : 'Error al enviar mensaje de Telegram. Verifique el token y Chat ID.'
        );
    }
}
