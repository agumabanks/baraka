<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\MobileBank\MobileBankInterface;
use Illuminate\Http\Request;

class MobileBankController extends Controller
{
    public function __construct(protected MobileBankInterface $repo) {}

    public function index()
    {
        $mobile_banks = $this->repo->all();
        return view('backend.mobile_bank.index', [
            'mobile_banks' => $mobile_banks,
        ]);
    }

    public function create()
    {
        return view('backend.mobile_bank.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:mobile_banks,name',
        ]);
        $this->repo->create($data);
        return redirect()->route('mobile-bank.index');
    }

    public function edit($id)
    {
        $mobile_bank = $this->repo->find($id);
        return view('backend.mobile_bank.edit', [
            'mobile_bank' => $mobile_bank,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $this->repo->update($id, $data);
        return redirect()->route('mobile-bank.index');
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return redirect()->route('mobile-bank.index');
    }

    public function filter(Request $request)
    {
        $mobile_banks = $this->repo->filter($request);
        return view('backend.mobile_bank.index', [
            'mobile_banks' => $mobile_banks,
            'request' => $request,
        ]);
    }
}
