<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class LifecycleEmailPreferenceController extends Controller
{
    public function unsubscribe(User $user)
    {
        if ($user->acceptsLifecycleEmail()) {
            $user->forceFill(['lifecycle_opt_out_at' => now()])->save();
            Log::info('[Lifecycle] opted out', ['user_id' => $user->id]);
        }

        return view('emails.unsubscribed', ['email' => $user->email]);
    }
}
