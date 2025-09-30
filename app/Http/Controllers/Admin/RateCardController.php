<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use Illuminate\Http\Request;

class RateCardController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', RateCard::class);
        $items = RateCard::latest()->paginate(15);

        return view('backend.admin.rate_cards.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', RateCard::class);

        return view('backend.admin.rate_cards.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', RateCard::class);

        return back()->with('status', 'Rate card creation not yet implemented');
    }

    public function update(Request $request, RateCard $rate_card)
    {
        $this->authorize('update', $rate_card);

        return back()->with('status', 'Rate card update not yet implemented');
    }
}
