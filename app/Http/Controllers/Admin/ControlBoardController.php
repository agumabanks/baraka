<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ControlBoardController extends Controller
{
    public function index()
    {
        return view('backend.admin.control_board.index');
    }
}
