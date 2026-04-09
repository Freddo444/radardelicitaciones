<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TrialInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with('owner');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->whereHas('owner', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $subscriptions = $query->latest()->paginate(25)->withQueryString();

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function updateStatus(Request $request, Subscription $subscription)
    {
        $request->validate([
            'status' => 'required|in:pending,active,past_due,cancelled,suspended,trialing',
        ]);

        $old = $subscription->status;
        $subscription->update(['status' => $request->status]);

        if ($request->status === 'active' && ! $subscription->current_period_end) {
            $subscription->update([
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        return back()->with('success', "Suscripcion #{$subscription->id}: {$old} → {$request->status}");
    }

    public function grantTrial(Request $request, Subscription $subscription)
    {
        $request->validate([
            'duration' => 'required|integer|min:1|max:365',
            'parse_limit' => 'required|integer|min:0|max:9999',
        ]);

        $subscription->update([
            'status' => 'trialing',
            'plan' => 'trial',
            'trial_ends_at' => now()->addDays((int) $request->duration),
            'trial_parse_count' => 0,
            'trial_parse_limit' => (int) $request->parse_limit,
            'monthly_amount' => 0,
        ]);

        $days = (int) $request->duration;
        $owner = $subscription->owner?->name ?? 'Unknown';

        return back()->with('success', "Trial de {$days} dias otorgado a {$owner} (limite: {$request->parse_limit} analisis).");
    }

    public function createTrial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'duration' => 'required|integer|min:1|max:365',
            'parse_limit' => 'required|integer|min:0|max:9999',
        ]);

        $password = Str::random(10);

        try {
            $user = DB::transaction(function () use ($request, $password) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);

                Subscription::create([
                    'user_id' => $user->id,
                    'plan' => 'trial',
                    'status' => 'trialing',
                    'max_companies' => 1,
                    'max_users' => 2,
                    'monthly_amount' => 0,
                    'trial_ends_at' => now()->addDays((int) $request->duration),
                    'trial_parse_count' => 0,
                    'trial_parse_limit' => (int) $request->parse_limit,
                ]);

                return $user;
            });
        } catch (QueryException $e) {
            Log::error('[Admin] createTrial failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput($request->except('password'))
                ->with('error', 'No se pudo crear el trial. Verifica que el correo no exista y que la base de datos esté actualizada.');
        }

        try {
            Mail::to($user->email)->send(new TrialInvitation($user, $password, (int) $request->duration));
        } catch (\Throwable $e) {
            Log::error('[Admin] createTrial mail failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return back()->with('info', "Cuenta creada para {$user->email}, pero no se pudo enviar el correo. Restablece contraseña o reenvía credenciales manualmente.");
        }

        $days = (int) $request->duration;

        return back()->with('success', "Trial de {$days} dias creado para {$user->name} ({$user->email}). Email enviado con credenciales.");
    }
}
