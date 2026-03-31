<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private const API_BASE = 'https://api.telegram.org/bot';

    public function sendMessage(string $text, ?int $companyId = null): bool
    {
        $cid = $companyId ?? currentCompany()?->id;
        $token = Setting::get('telegram_bot_token', null, $cid) ?? config('services.telegram.bot_token');
        $chatId = Setting::get('telegram_chat_id', null, $cid) ?? config('services.telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            Log::warning('[Telegram] Bot token or chat ID not configured.', ['company_id' => $cid]);

            return false;
        }

        $response = Http::timeout(15)->post(self::API_BASE.$token.'/sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if ($response->failed()) {
            Log::error('[Telegram] sendMessage failed', [
                'company_id' => $cid,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function isConfigured(?int $companyId = null): bool
    {
        $cid = $companyId ?? currentCompany()?->id;
        $token = Setting::get('telegram_bot_token', null, $cid);
        $chatId = Setting::get('telegram_chat_id', null, $cid);

        return ! empty($token) && ! empty($chatId);
    }
}
