<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return view('backend.admin.search.index', ['results' => collect(), 'q' => '']);
        }

        $driver = env('SEARCH_DRIVER', 'pgsql_fts');
        $results = collect();

        if ($driver === 'pgsql_fts' && config('database.default') === 'pgsql') {
            $tsq = DB::raw("websearch_to_tsquery('simple', ?)");
            $rows = DB::select("
              SELECT 'shipment' as type, s.id, s.tracking, s.current_status, c.name as customer
              FROM shipments s
              LEFT JOIN users c ON c.id = s.customer_id
              WHERE to_tsvector('simple', coalesce(s.tracking,'') || ' ' || coalesce(c.name,'') || ' ' || coalesce(c.phone_e164,'')) @@ {$tsq}
              ORDER BY s.id DESC LIMIT 50
            ", [$q]);
            $results = collect($rows)->map(function ($r) {
                $r->url = route('admin.shipments.show', $r->id);
                $r->title = $r->tracking ?: ('Shipment #'.$r->id);
                $r->subtitle = trim(($r->customer ?? '').' · '.($r->current_status ?? ''));
                return $r;
            });
        } else {
            // Fallback naive search (for MySQL or others)
            $rows = DB::table('shipments as s')
                ->leftJoin('users as c', 'c.id', '=', 's.customer_id')
                ->select(DB::raw("'shipment' as type"), 's.id', 's.tracking', 's.current_status', DB::raw('c.name as customer'))
                ->where(function ($query) use ($q) {
                    $query->where('s.tracking', 'like', "%$q%")
                          ->orWhere('c.name', 'like', "%$q%")
                          ->orWhere('c.phone_e164', 'like', "%$q%");
                })
                ->orderByDesc('s.id')
                ->limit(50)
                ->get();
            $results = collect($rows)->map(function ($r) {
                $r->url = route('admin.shipments.show', $r->id);
                $r->title = $r->tracking ?: ('Shipment #'.$r->id);
                $r->subtitle = trim(($r->customer ?? '').' · '.($r->current_status ?? ''));
                return $r;
            });
        }

        return view('backend.admin.search.index', ['results' => $results, 'q' => $q]);
    }
}
