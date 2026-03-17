<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use Illuminate\Http\Request;

class ConvocatoriasController extends Controller
{
    public function index(Request $request)
    {
        $query = Bid::query();

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('process_code', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        // Method filter
        if ($method = $request->input('modalidad')) {
            $query->where('procurement_method', $method);
        }

        // Only open deadlines
        if ($request->boolean('vigentes')) {
            $query->where(function ($q) {
                $q->whereNull('tender_deadline')
                    ->orWhere('tender_deadline', '>=', now());
            });
        }

        $bids = $query->orderByDesc('published_at')->paginate(25)->withQueryString();

        // For filter dropdowns
        $statuses = Bid::select('status')->distinct()->whereNotNull('status')->orderBy('status')->pluck('status');
        $methods = Bid::select('procurement_method')->distinct()->whereNotNull('procurement_method')->orderBy('procurement_method')->pluck('procurement_method');

        return view('convocatorias.index', compact('bids', 'statuses', 'methods'));
    }
}
