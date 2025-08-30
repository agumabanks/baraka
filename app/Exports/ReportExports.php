<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromView;

class ReportExports implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */


    public function view(): View
    {
 
        return View('backend.reports.print_view',[
            'user_type'=>Request::input('user_type'),
            'total_parcel'=>Request::input('total_parcel')
        ]);
    }
}
