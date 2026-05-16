<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNewsletterController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = User::query()
            ->where('is_super_admin', false)
            ->where('newsletter_subscribed', true)
            ->orderByDesc('newsletter_consented_at')
            ->orderByDesc('id');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->paginate(50)->withQueryString();

        return view('admin.newsletter.index', compact('subscribers'));
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = User::query()
            ->where('is_super_admin', false)
            ->where('newsletter_subscribed', true)
            ->orderBy('email');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $filename = 'newsletter-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'newsletter_consented_at']);

            $query->lazyById(500, 'id')->each(function (User $user) use ($out) {
                fputcsv($out, [
                    $user->email,
                    $user->name,
                    $user->newsletter_consented_at?->toIso8601String() ?? '',
                ]);
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->is_super_admin) {
            abort(403);
        }

        $request->validate([
            'subscribed' => 'required|boolean',
        ]);

        $subscribed = $request->boolean('subscribed');

        $user->update([
            'newsletter_subscribed' => $subscribed,
            'newsletter_consented_at' => $subscribed ? now() : null,
        ]);

        return back()->with('success', $subscribed
            ? 'Usuario agregado a la lista de novedades.'
            : 'Usuario quitado de la lista de novedades.');
    }
}
