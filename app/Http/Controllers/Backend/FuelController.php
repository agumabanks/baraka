<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fuel\StoreRequest;
use App\Models\Backend\Asset;
use App\Repositories\Fuels\FuelsInterface;
use Brian2694\Toastr\Facades\Toastr;

class FuelController extends Controller
{
    protected $repo;

    public function __construct(FuelsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $fuels = $this->repo->all();

        return view('backend.fuels.index', compact('fuels'));
    }

    public function create()
    {

        $assets = Asset::orderBy('id', 'desc')->get();

        return view('backend.fuels.create', compact('assets'));
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Fuel successfully added.', __('message.success'));

            return redirect()->route('fuels.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function edit($id)
    {

        $fuel = $this->repo->get($id);
        $assets = Asset::orderBy('id', 'desc')->get();

        return view('backend.fuels.edit', compact('assets', 'fuel'));
    }

    public function update(StoreRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success('Fuel successfully updated.', __('message.success'));

            return redirect()->route('fuels.index');
        } else {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function delete($id)
    {
        $this->repo->delete($id);
        Toastr::success('Fuel successfully deleted.', __('message.success'));

        return back();
    }
}
