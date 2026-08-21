<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminHealthController extends Controller
{
    public function index()
    {
        $queue = $this->buildQueue();
        $freshness = $this->buildFreshness();
        $notifications = $this->buildNotifications();
        $billing = $this->buildBilling();
        $integrations = $this->buildIntegrations($freshness);

        return view('admin.health', compact('queue', 'freshness', 'notifications', 'billing', 'integrations'));
    }

    private function buildQueue(): array
    {
        $hasJobs = Schema::hasTable('jobs');
        $hasFailed = Schema::hasTable('failed_jobs');

        $pending = $hasJobs ? DB::table('jobs')->count() : null;
        $failed = $hasFailed ? DB::table('failed_jobs')->count() : null;

        $recentFailed = collect();
        if ($hasFailed) {
            $recentFailed = DB::table('failed_jobs')
                ->latest('failed_at')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $firstLine = strtok((string) $row->exception, "\n");

                    return (object) [
                        'id' => $row->id,
                        'queue' => $row->queue,
                        'failed_at' => $row->failed_at,
                        'exception' => Str::limit((string) $firstLine, 160),
                    ];
                });
        }

        return [
            'hasJobs' => $hasJobs,
            'hasFailed' => $hasFailed,
            'pending' => $pending,
            'failed' => $failed,
            'recentFailed' => $recentFailed,
        ];
    }

    private function buildFreshness(): array
    {
        return [
            'poll' => $this->freshnessItem('last_polled_at', 120),
            'scrape' => $this->freshnessItem('last_scraped_at', 60),
            'catalog' => $this->freshnessItem('catalog_last_imported_at', 40 * 24 * 60),
        ];
    }

    private function freshnessItem(string $key, int $staleMinutes): array
    {
        $raw = Setting::get($key, null, null);

        if (empty($raw)) {
            return ['raw' => null, 'ago' => null, 'stale' => true, 'missing' => true];
        }

        $ts = Carbon::parse($raw);
        $ageMin = $ts->diffInMinutes(now());

        return [
            'raw' => $ts,
            'ago' => $ts->diffForHumans(),
            'stale' => $ageMin > $staleMinutes,
            'missing' => false,
        ];
    }

    private function buildNotifications(): array
    {
        $since = now()->subDay();

        $rows = NotificationLog::withoutGlobalScopes()
            ->where('created_at', '>=', $since)
            ->select('channel', 'status', DB::raw('count(*) as total'))
            ->groupBy('channel', 'status')
            ->get();

        $channels = [];
        foreach ($rows as $row) {
            $channel = $row->channel ?: 'desconocido';
            if (! isset($channels[$channel])) {
                $channels[$channel] = ['sent' => 0, 'failed' => 0, 'other' => 0];
            }

            if ($row->status === 'sent') {
                $channels[$channel]['sent'] += $row->total;
            } elseif ($row->status === 'failed') {
                $channels[$channel]['failed'] += $row->total;
            } else {
                $channels[$channel]['other'] += $row->total;
            }
        }

        return $channels;
    }

    private function buildBilling(): array
    {
        $statuses = ['active', 'trialing', 'past_due', 'suspended', 'cancelled', 'pending'];

        $counts = Subscription::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = [];
        foreach ($statuses as $status) {
            $byStatus[$status] = (int) ($counts[$status] ?? 0);
        }

        $pendingTransfers = Payment::where('gateway', 'bank_transfer')
            ->where('status', 'pending')
            ->count();

        return [
            'byStatus' => $byStatus,
            'pendingTransfers' => $pendingTransfers,
        ];
    }

    private function buildIntegrations(array $freshness): array
    {
        $mailDefault = config('mail.default');
        $pollFresh = ! $freshness['poll']['stale'] && ! $freshness['poll']['missing'];

        return [
            ['label' => 'Correo ('.$mailDefault.')', 'ok' => $mailDefault !== 'log' && ! empty($mailDefault), 'note' => $mailDefault === 'log' ? 'Modo log' : null],
            ['label' => 'Gemini (IA pliegos)', 'ok' => ! empty(config('services.gemini.key'))],
            ['label' => 'Telegram global', 'ok' => ! empty(config('services.telegram.bot_token'))],
            ['label' => 'PayPal', 'ok' => ! empty(config('services.paypal.client_id'))],
            ['label' => 'Azul', 'ok' => ! empty(config('services.azul.merchant_id'))],
            ['label' => 'Sentry', 'ok' => ! empty(config('sentry.dsn'))],
            ['label' => 'DGCP API', 'ok' => $pollFresh, 'note' => $pollFresh ? null : 'Verificar'],
        ];
    }
}
