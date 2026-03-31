<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('login')
                ->with('info', 'Esta invitacion ya fue aceptada.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'Esta invitacion ha expirado.');
        }

        $invitation->load('company', 'inviter');
        $existingUser = User::where('email', $invitation->email)->first();

        return view('invitations.accept', compact('invitation', 'existingUser'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('login')
                ->with('info', 'Esta invitacion ya fue aceptada.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'Esta invitacion ha expirado.');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Existing user — just add to company
            $user = $existingUser;
        } else {
            // New user — must register
            $request->validate([
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
            ]);
        }

        // Attach to company if not already
        $company = $invitation->company;
        if (! $company->users()->where('users.id', $user->id)->exists()) {
            $company->users()->attach($user->id, ['joined_at' => now()]);
        }

        // Set current company if user has none
        if (! $user->current_company_id) {
            $user->update(['current_company_id' => $company->id]);
        }

        // Mark invitation accepted
        $invitation->update(['accepted_at' => now()]);

        // Log in the user
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', "Te has unido a {$company->razon_social}.");
    }
}
