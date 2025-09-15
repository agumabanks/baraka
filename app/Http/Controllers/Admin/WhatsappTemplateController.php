<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class WhatsappTemplateController extends Controller
{
    public function index()
    {
        $templates = WhatsappTemplate::paginate(20);
        return view('backend.admin.whatsapp_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('backend.admin.whatsapp_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:whatsapp_templates,name',
            'language' => 'nullable|string|max:10',
            'body' => 'required|string',
            'approved' => 'nullable|boolean',
        ]);
        $data['approved'] = (bool)($data['approved'] ?? false);
        WhatsappTemplate::create($data);
        return redirect()->route('admin.whatsapp-templates.index')->with('status', 'Template created');
    }
}

