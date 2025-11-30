<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Account;
use App\Models\Backend\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function accounts(): View
    {
        $accounts = Account::latest()->paginate(20);
        
        $stats = [
            'total_accounts' => Account::count(),
            'total_balance' => Account::sum('balance') ?? 0,
        ];

        return view('admin.finance.accounts', compact('accounts', 'stats'));
    }

    public function transactions(): View
    {
        $transactions = BankTransaction::latest()->paginate(20);
        
        $stats = [
            'total_transactions' => BankTransaction::count(),
            'total_income' => BankTransaction::where('type', 1)->sum('amount') ?? 0,
            'total_expense' => BankTransaction::where('type', 2)->sum('amount') ?? 0,
        ];

        return view('admin.finance.transactions', compact('transactions', 'stats'));
    }

    public function statements(): View
    {
        return view('admin.finance.statements');
    }
}
