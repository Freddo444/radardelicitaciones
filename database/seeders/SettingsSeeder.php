<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'poll_interval_minutes' => '60',
            'last_polled_at'        => null,
            'notification_email'    => null,
            'telegram_bot_token'    => null,
            'telegram_chat_id'      => null,
            'min_amount_filter'     => '0',
            'min_amount_value'      => '0',
            'min_amount_currency'   => 'DOP',
            'max_amount_filter'     => '0',
            'max_amount_value'      => '0',
            'max_amount_currency'   => 'DOP',
            'excluded_modalities'   => '[]',
            'poll_status'           => 'idle',
            'poll_log'              => '[]',
            'poll_started_at'       => null,
        ];

        foreach ($defaults as $key => $value) {
            \DB::table('settings')->insertOrIgnore([
                'key'        => $key,
                'value'      => $value,
                'updated_at' => now(),
            ]);
        }
    }
}
