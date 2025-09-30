<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index()
    {
        $hooks = \App\Models\Webhook::query()->latest('id')->paginate(20);

        return view('backend.admin.webhooks.index', compact('hooks'));
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Webhook creation not implemented');
    }

    public function destroy($id)
    {
        return back()->with('status', 'Webhook delete not implemented');
    }
}
