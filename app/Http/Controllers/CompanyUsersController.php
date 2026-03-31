<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CompanyUsersController extends Controller
{
    public function index()
    {
        $company = currentCompany();
        $users = $company->users()->orderBy('name')->get();

        $pendingInvitations = Invitation::where('company_id', $company->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        $subscription = $this->ownerSubscription();
        $isOwner = $subscription && auth()->id() === $subscription->user_id;
        $canInvite = $isOwner && $subscription->isActive() && SubscriptionService::canAddUser($subscription);

        return view('company-users.index', compact(
            'company', 'users', 'pendingInvitations', 'subscription', 'isOwner', 'canInvite'
        ));
    }

    public function invite(Request $request)
    {
        $subscription = $this->ownerSubscription();

        if (! $subscription || auth()->id() !== $subscription->user_id) {
            return back()->with('error', 'Solo el propietario de la suscripcion puede invitar usuarios.');
        }

        if (! SubscriptionService::canAddUser($subscription)) {
            return back()->with('error', "Limite de usuarios alcanzado ({$subscription->max_users}). Actualiza tu plan para agregar mas.");
        }

        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $company = currentCompany();
        $email = strtolower($request->email);

        // Already a member?
        if ($company->users()->where('email', $email)->exists()) {
            return back()->with('error', 'Este usuario ya es miembro de la empresa.');
        }

        // Already invited (pending)?
        $existing = Invitation::where('company_id', $company->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return back()->with('error', 'Ya existe una invitacion pendiente para este email.');
        }

        $invitation = Invitation::create([
            'company_id' => $company->id,
            'email' => $email,
            'invited_by' => auth()->id(),
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new InvitationMail($invitation));

        return back()->with('success', "Invitacion enviada a {$email}.");
    }

    public function removeUser(Request $request, int $userId)
    {
        $subscription = $this->ownerSubscription();

        if (! $subscription || auth()->id() !== $subscription->user_id) {
            return back()->with('error', 'Solo el propietario de la suscripcion puede remover usuarios.');
        }

        // Can't remove yourself
        if ($userId === auth()->id()) {
            return back()->with('error', 'No puedes removerte a ti mismo.');
        }

        $company = currentCompany();
        $company->users()->detach($userId);

        return back()->with('success', 'Usuario removido de la empresa.');
    }

    public function cancelInvitation(Invitation $invitation)
    {
        $subscription = $this->ownerSubscription();

        if (! $subscription || auth()->id() !== $subscription->user_id) {
            return back()->with('error', 'Solo el propietario de la suscripcion puede cancelar invitaciones.');
        }

        if ($invitation->company_id !== currentCompany()->id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitacion cancelada.');
    }

    private function ownerSubscription(): ?Subscription
    {
        $company = currentCompany();

        return $company->owner_id
            ? Subscription::where('user_id', $company->owner_id)->first()
            : null;
    }
}
