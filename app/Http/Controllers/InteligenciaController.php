<?php

namespace App\Http\Controllers;

use App\Models\AwardedArticle;
use App\Models\Contract;
use App\Models\Institution;
use App\Models\PaccAcquisition;
use App\Models\Provider;
use App\Models\Rubro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InteligenciaController extends Controller
{
    public function adjudicados(Request $request)
    {
        $query = AwardedArticle::query();

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('provider_name', 'like', "%{$search}%")
                    ->orWhere('institution_name', 'like', "%{$search}%")
                    ->orWhere('process_code', 'like', "%{$search}%")
                    ->orWhere('contract_code', 'like', "%{$search}%")
                    ->orWhere('unspsc_description', 'like', "%{$search}%");
            });
        }

        // UNSPSC filters
        if ($familia = $request->input('familia')) {
            $query->where('unspsc_familia', $familia);
        }
        if ($clase = $request->input('clase')) {
            $query->where('unspsc_clase', $clase);
        }
        if ($subclase = $request->input('subclase')) {
            $query->where('unspsc_subclase', $subclase);
        }

        // Institution
        if ($institution = $request->input('institucion')) {
            $query->where('institution_code', $institution);
        }

        // Provider
        if ($provider = $request->input('proveedor')) {
            $query->where('provider_rpe', $provider);
        }

        // Amount range
        if ($minAmount = $request->input('monto_min')) {
            $query->where('total', '>=', (float) $minAmount);
        }
        if ($maxAmount = $request->input('monto_max')) {
            $query->where('total', '<=', (float) $maxAmount);
        }

        // Date range
        if ($dateFrom = $request->input('fecha_desde')) {
            $query->where('award_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('fecha_hasta')) {
            $query->where('award_date', '<=', $dateTo);
        }

        // Sorting
        $sortable = ['description', 'provider_name', 'institution_name', 'unit_price', 'total', 'quantity', 'award_date'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'award_date';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $articles = $query->orderBy($sort, $dir)->paginate(50)->withQueryString();

        // Filter dropdowns
        $institutions = AwardedArticle::select('institution_name', 'institution_code')
            ->whereNotNull('institution_name')
            ->where('institution_name', '!=', '')
            ->distinct()
            ->orderBy('institution_name')
            ->get();

        $familias = AwardedArticle::select('unspsc_familia', 'unspsc_description')
            ->whereNotNull('unspsc_familia')
            ->distinct()
            ->orderBy('unspsc_familia')
            ->get()
            ->unique('unspsc_familia');

        $totalCount = AwardedArticle::count();

        // Aggregates for the current filtered set
        $hasFilters = $request->hasAny(['q', 'familia', 'clase', 'subclase', 'institucion', 'proveedor', 'monto_min', 'monto_max', 'fecha_desde', 'fecha_hasta']);
        $aggregates = null;

        if ($hasFilters) {
            $aggQuery = clone $query;
            $agg = $aggQuery->selectRaw('
                COUNT(*) as total_articles,
                AVG(unit_price) as avg_unit_price,
                MIN(unit_price) as min_unit_price,
                MAX(unit_price) as max_unit_price,
                SUM(total) as sum_total,
                COUNT(DISTINCT provider_rpe) as unique_providers,
                COUNT(DISTINCT institution_code) as unique_institutions
            ')->first();

            // Price trend by month
            $trendQuery = clone $query;
            $priceTrend = $trendQuery
                ->select(DB::raw("DATE_FORMAT(award_date, '%Y-%m') as month"), DB::raw('AVG(unit_price) as avg_price'), DB::raw('COUNT(*) as article_count'))
                ->whereNotNull('award_date')
                ->where('unit_price', '>', 0)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Top providers by total awarded
            $topProviders = (clone $query)
                ->select('provider_name', 'provider_rpe', DB::raw('SUM(total) as awarded_total'), DB::raw('COUNT(*) as article_count'))
                ->whereNotNull('provider_name')
                ->where('provider_name', '!=', '')
                ->groupBy('provider_name', 'provider_rpe')
                ->orderByDesc('awarded_total')
                ->limit(5)
                ->get();

            // Top institutions by total awarded
            $topInstitutions = (clone $query)
                ->select('institution_name', 'institution_code', DB::raw('SUM(total) as awarded_total'), DB::raw('COUNT(*) as article_count'))
                ->whereNotNull('institution_name')
                ->where('institution_name', '!=', '')
                ->groupBy('institution_name', 'institution_code')
                ->orderByDesc('awarded_total')
                ->limit(5)
                ->get();

            $aggregates = [
                'summary' => $agg,
                'price_trend' => $priceTrend,
                'top_providers' => $topProviders,
                'top_institutions' => $topInstitutions,
            ];
        }

        return view('inteligencia.adjudicados', compact(
            'articles',
            'institutions',
            'familias',
            'totalCount',
            'aggregates',
        ));
    }

    public function pacc(Request $request)
    {
        $query = PaccAcquisition::query();

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('institution_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('unspsc_description', 'like', "%{$search}%");
            });
        }

        // UNSPSC filters
        if ($familia = $request->input('familia')) {
            $query->where('unspsc_familia', $familia);
        }
        if ($clase = $request->input('clase')) {
            $query->where('unspsc_clase', $clase);
        }
        if ($subclase = $request->input('subclase')) {
            $query->where('unspsc_subclase', $subclase);
        }

        // Institution
        if ($institution = $request->input('institucion')) {
            $query->where('institution_code', $institution);
        }

        // Modality
        if ($modality = $request->input('modalidad')) {
            $query->where('modality', $modality);
        }

        // Object type
        if ($objectType = $request->input('tipo_objeto')) {
            $query->where('object_type', $objectType);
        }

        // MIPYMES
        if ($request->input('mipymes') === '1') {
            $query->where('mipymes', true);
        }
        if ($request->input('mipymes_mujeres') === '1') {
            $query->where('mipymes_mujeres', true);
        }

        // Amount range
        if ($minAmount = $request->input('monto_min')) {
            $query->where('estimated_amount', '>=', (float) $minAmount);
        }
        if ($maxAmount = $request->input('monto_max')) {
            $query->where('estimated_amount', '<=', (float) $maxAmount);
        }

        // Date range
        if ($dateFrom = $request->input('fecha_desde')) {
            $query->where('start_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('fecha_hasta')) {
            $query->where('start_date', '<=', $dateTo);
        }

        // Sorting
        $sortable = ['description', 'institution_name', 'estimated_amount', 'start_date', 'modality', 'object_type'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'start_date';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $acquisitions = $query->orderBy($sort, $dir)->paginate(50)->withQueryString();

        // Filter dropdowns
        $institutions = PaccAcquisition::select('institution_name', 'institution_code')
            ->whereNotNull('institution_name')
            ->where('institution_name', '!=', '')
            ->distinct()
            ->orderBy('institution_name')
            ->get();

        $modalities = PaccAcquisition::select('modality')
            ->whereNotNull('modality')
            ->where('modality', '!=', '')
            ->distinct()
            ->orderBy('modality')
            ->pluck('modality');

        $objectTypes = PaccAcquisition::select('object_type')
            ->whereNotNull('object_type')
            ->where('object_type', '!=', '')
            ->distinct()
            ->orderBy('object_type')
            ->pluck('object_type');

        $familias = PaccAcquisition::select('unspsc_familia', 'unspsc_description')
            ->whereNotNull('unspsc_familia')
            ->distinct()
            ->orderBy('unspsc_familia')
            ->get()
            ->unique('unspsc_familia');

        $totalCount = PaccAcquisition::count();

        // Highlight acquisitions matching user's active rubros
        $activeRubros = Rubro::where('active', true)->get();
        $matchedRubroCodes = [];
        foreach ($activeRubros as $rubro) {
            $matchedRubroCodes[$rubro->level][] = $rubro->code;
        }

        // Aggregates when filters active
        $hasFilters = $request->hasAny(['q', 'familia', 'clase', 'subclase', 'institucion', 'modalidad', 'tipo_objeto', 'mipymes', 'mipymes_mujeres', 'monto_min', 'monto_max', 'fecha_desde', 'fecha_hasta']);
        $aggregates = null;

        if ($hasFilters) {
            $agg = (clone $query)->selectRaw('
                COUNT(*) as total_acquisitions,
                SUM(estimated_amount) as sum_estimated,
                AVG(estimated_amount) as avg_estimated,
                MIN(estimated_amount) as min_estimated,
                MAX(estimated_amount) as max_estimated,
                COUNT(DISTINCT institution_code) as unique_institutions,
                SUM(CASE WHEN mipymes = 1 THEN 1 ELSE 0 END) as mipymes_count
            ')->first();

            $byModality = (clone $query)
                ->select('modality', DB::raw('COUNT(*) as count'), DB::raw('SUM(estimated_amount) as total'))
                ->whereNotNull('modality')
                ->where('modality', '!=', '')
                ->groupBy('modality')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            $byInstitution = (clone $query)
                ->select('institution_name', 'institution_code', DB::raw('COUNT(*) as count'), DB::raw('SUM(estimated_amount) as total'))
                ->whereNotNull('institution_name')
                ->where('institution_name', '!=', '')
                ->groupBy('institution_name', 'institution_code')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $aggregates = [
                'summary' => $agg,
                'by_modality' => $byModality,
                'by_institution' => $byInstitution,
            ];
        }

        return view('inteligencia.pacc', compact(
            'acquisitions',
            'institutions',
            'modalities',
            'objectTypes',
            'familias',
            'totalCount',
            'aggregates',
            'matchedRubroCodes',
        ));
    }

    public function contratos(Request $request)
    {
        $query = Contract::query();

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('provider_name', 'like', "%{$search}%")
                    ->orWhere('institution_name', 'like', "%{$search}%")
                    ->orWhere('process_code', 'like', "%{$search}%")
                    ->orWhere('contract_code', 'like', "%{$search}%");
            });
        }

        // Institution
        if ($institution = $request->input('institucion')) {
            $query->where('institution_code', $institution);
        }

        // Provider
        if ($provider = $request->input('proveedor')) {
            $query->where('provider_rpe', $provider);
        }

        // Status
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        // Amount range
        if ($minAmount = $request->input('monto_min')) {
            $query->where('amount', '>=', (float) $minAmount);
        }
        if ($maxAmount = $request->input('monto_max')) {
            $query->where('amount', '<=', (float) $maxAmount);
        }

        // Date range (contract date)
        if ($dateFrom = $request->input('fecha_desde')) {
            $query->where('contract_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('fecha_hasta')) {
            $query->where('contract_date', '<=', $dateTo);
        }

        // Sorting
        $sortable = ['contract_code', 'provider_name', 'institution_name', 'amount', 'status', 'contract_date', 'award_date'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'contract_date';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $contracts = $query->orderBy($sort, $dir)->paginate(50)->withQueryString();

        // Filter dropdowns
        $institutions = Contract::select('institution_name', 'institution_code')
            ->whereNotNull('institution_name')
            ->where('institution_name', '!=', '')
            ->distinct()
            ->orderBy('institution_name')
            ->get();

        $providers = Contract::select('provider_name', 'provider_rpe')
            ->whereNotNull('provider_name')
            ->where('provider_name', '!=', '')
            ->distinct()
            ->orderBy('provider_name')
            ->get();

        $statuses = Contract::select('status')
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $totalCount = Contract::count();

        // Aggregates when filters active
        $hasFilters = $request->hasAny(['q', 'institucion', 'proveedor', 'estado', 'monto_min', 'monto_max', 'fecha_desde', 'fecha_hasta']);
        $aggregates = null;

        if ($hasFilters) {
            $agg = (clone $query)->selectRaw('
                COUNT(*) as total_contracts,
                SUM(amount) as sum_amount,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                COUNT(DISTINCT provider_rpe) as unique_providers,
                COUNT(DISTINCT institution_code) as unique_institutions
            ')->first();

            $topProviders = (clone $query)
                ->select('provider_name', 'provider_rpe', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as contract_count'))
                ->whereNotNull('provider_name')
                ->where('provider_name', '!=', '')
                ->groupBy('provider_name', 'provider_rpe')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get();

            $topInstitutions = (clone $query)
                ->select('institution_name', 'institution_code', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as contract_count'))
                ->whereNotNull('institution_name')
                ->where('institution_name', '!=', '')
                ->groupBy('institution_name', 'institution_code')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get();

            $byStatus = (clone $query)
                ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->groupBy('status')
                ->orderByDesc('total')
                ->get();

            $aggregates = [
                'summary' => $agg,
                'top_providers' => $topProviders,
                'top_institutions' => $topInstitutions,
                'by_status' => $byStatus,
            ];
        }

        return view('inteligencia.contratos', compact(
            'contracts',
            'institutions',
            'providers',
            'statuses',
            'totalCount',
            'aggregates',
        ));
    }

    public function proveedores(Request $request)
    {
        $query = Provider::query();

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('rnc', 'like', "%{$search}%")
                    ->orWhere('rpe', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        // Status
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        // MIPYME
        if ($request->input('mipyme') === '1') {
            $query->where('is_mipyme', true);
        }

        // Tipo persona
        if ($tipo = $request->input('tipo_persona')) {
            $query->where('tipo_persona', $tipo);
        }

        // Province
        if ($province = $request->input('provincia')) {
            $query->where('province', $province);
        }

        // Sorting
        $sortable = ['razon_social', 'rpe', 'rnc', 'status', 'province', 'tipo_persona'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'razon_social';
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $providers = $query->orderBy($sort, $dir)->paginate(50)->withQueryString();

        // Filter dropdowns
        $statuses = Provider::select('status')
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $tiposPersona = Provider::select('tipo_persona')
            ->whereNotNull('tipo_persona')
            ->where('tipo_persona', '!=', '')
            ->distinct()
            ->orderBy('tipo_persona')
            ->pluck('tipo_persona');

        $provinces = Provider::select('province')
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        $totalCount = Provider::count();

        return view('inteligencia.proveedores', compact(
            'providers',
            'statuses',
            'tiposPersona',
            'provinces',
            'totalCount',
        ));
    }

    public function instituciones(Request $request)
    {
        $query = Institution::query();

        // Search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('acronym', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status
        if ($status = $request->input('estado')) {
            $query->where('status', $status);
        }

        // Sorting
        $sortable = ['name', 'code', 'acronym', 'status'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'name';
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $institutions = $query->orderBy($sort, $dir)->paginate(50)->withQueryString();

        // Filter dropdowns
        $statuses = Institution::select('status')
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $totalCount = Institution::count();

        return view('inteligencia.instituciones', compact(
            'institutions',
            'statuses',
            'totalCount',
        ));
    }
}
