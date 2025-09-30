<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\Bank\BankInterface;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function __construct(
        protected BankInterface $repo
    ) {}

    public function index()
    {
        $banks = $this->repo->all();

        return view('backend.bank.index', [
            'banks' => $banks,
        ]);
    }

    public function create()
    {
        return view('backend.bank.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:banks,name',
        ]);

        $this->repo->create($data);

        return redirect()->route('bank.index');
    }

    public function edit($id)
    {
        $bank = $this->repo->find($id);

        return view('backend.bank.edit', [
            'bank' => $bank,
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->repo->update($id, $data);

        return redirect()->route('bank.index');
    }

    public function destroy($id)
    {
        $this->repo->delete($id);

        return redirect()->route('bank.index');
    }

    public function filter(Request $request)
    {
        $banks = $this->repo->filter($request);

        return view('backend.bank.index', [
            'banks' => $banks,
            'request' => $request,
        ]);
    }
}
