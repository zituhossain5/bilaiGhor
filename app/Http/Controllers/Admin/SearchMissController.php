<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchMiss;
use Illuminate\Http\Request;

/** Storefront searches that found nothing — review them to fix product names and keywords. */
class SearchMissController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-view');
    }

    public function index(Request $request)
    {
        $sort = $request->get('sort') === 'count' ? 'count' : 'recent';

        $misses = SearchMiss::query()
            ->when($request->filled('q'), fn ($query) => $query->where('query', 'like', '%' . addcslashes($request->q, '%_\\') . '%'))
            ->when(
                $sort === 'count',
                fn ($query) => $query->orderByDesc('search_count')->orderByDesc('last_searched_at'),
                fn ($query) => $query->orderByDesc('last_searched_at')
            )
            ->paginate(50)
            ->withQueryString();

        $totals = [
            'queries'  => SearchMiss::count(),
            'searches' => (int) SearchMiss::sum('search_count'),
            'week'     => SearchMiss::where('last_searched_at', '>=', now()->subDays(7))->count(),
        ];

        return view('backEnd.reports.search_misses', compact('misses', 'totals', 'sort'));
    }

    /** "Resolved" — the product was renamed/tagged; the query is logged again if it still misses. */
    public function destroy(SearchMiss $searchMiss)
    {
        $searchMiss->delete();

        return back()->with('success', 'Search "' . $searchMiss->query . '" marked as resolved.');
    }
}
