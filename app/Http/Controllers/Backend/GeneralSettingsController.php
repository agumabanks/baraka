<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsFormRequest;
use App\Repositories\Currency\CurrencyInterface;
use App\Repositories\GeneralSettings\GeneralSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GeneralSettingsController extends Controller
{
    protected $repo;

    protected $currency;

    public function __construct(GeneralSettingsInterface $repo, CurrencyInterface $currency)
    {
        $this->repo = $repo;
        $this->currency = $currency;
    }

    public function index()
    {
        $settings = $this->repo->all();
        $currencies = $this->currency->getActive();

        return view('settings.index', compact('settings', 'currencies'));
    }

    public function update(SettingsFormRequest $request)
    {
        if (env('DEMO')) {
            Toastr::error('Update system is disable for the demo mode.', 'Error');

            return redirect()->back();
        }
        
        $settings = $this->repo->update($request);
        // Invalidate settings cache
        Cache::forget('settings');
        Toastr::success(__('settings.save_change'), __('message.success'));

        return redirect()->route('settings.index');
    }
}
