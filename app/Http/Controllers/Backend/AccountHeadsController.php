<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Repositories\AccountHeads\AccountHeadsInterface;

class AccountHeadsController extends Controller
{
    protected $repo;

    public function __construct(AccountHeadsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $account_heads = $this->repo->all();

        return view('backend.account_heads.index', compact('account_heads'));
    }
}
