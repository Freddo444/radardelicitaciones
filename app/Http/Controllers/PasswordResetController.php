<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
        }

        // Don't reveal whether the email exists — always show success-like message
        return back()->with('status', 'Si el correo existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Tu contraseña ha sido restablecida. Ya puedes iniciar sesión.');
        }

        return back()->withErrors(['email' => $this->translateStatus($status)]);
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'El enlace de restablecimiento ha expirado o es inválido. Solicita uno nuevo.',
            Password::INVALID_USER => 'No encontramos un usuario con ese correo electrónico.',
            Password::RESET_THROTTLED => 'Has solicitado demasiados enlaces. Espera unos minutos e intenta de nuevo.',
            default => 'No se pudo restablecer la contraseña. Intenta de nuevo.',
        };
    }
}
