<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index()
    {
        $keys = \App\Models\ApiKey::query()->latest('id')->paginate(20);
        return view('backend.admin.api_keys.index', compact('keys'));
    }

    public function store(Request $request)
    {
        $name = $request->input('name', 'Key '.now()->format('Y-m-d H:i'));
        $token = bin2hex(random_bytes(20));
        \App\Models\ApiKey::create(['name'=>$name,'token'=>$token]);
        return back()->with('status','API key generated');
    }

    public function destroy($id)
    {
        if ($key = \App\Models\ApiKey::find($id)) {
            $key->delete();
        }
        return back()->with('status','API key deleted');
    }
}
