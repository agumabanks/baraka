<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GlExportController extends Controller
{
    public function index()
    {
        return view('backend.admin.gl_export.index');
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'gl_export_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['date', 'doc_no', 'account', 'debit', 'credit', 'currency']);
            DB::table('invoices')->limit(100)->orderByDesc('id')->chunk(100, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        optional($row->created_at)->format('Y-m-d') ?? date('Y-m-d'),
                        'INV-'.$row->id,
                        '1100-AR',
                        number_format((float) ($row->total ?? 0), 2, '.', ''),
                        '0.00',
                        $row->currency ?? 'USD',
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
