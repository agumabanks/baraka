<?php

namespace App\Http\Controllers\Backend\MerchantPanel;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Backend\NewsOffer;

class NewsOfferController extends Controller
{
    public function index()
    {
        $news_offers = NewsOffer::where('status', Status::ACTIVE)->with('upload')->orderByDesc('id')->paginate(10);

        return view('backend.merchant_panel.news_offer.index', compact('news_offers'));
    }
}
