<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manifest;

class ManifestController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Manifest::class);
        $items = Manifest::query()->latest('id')->paginate(15);
        return view('backend.admin.placeholder', ['title'=>'Manifests','items'=>$items]);
    }

    public function show(Manifest $manifest)
    {
        $this->authorize('view', $manifest);
        return view('backend.admin.placeholder', ['title'=>'Manifest','record'=>$manifest]);
    }
}

