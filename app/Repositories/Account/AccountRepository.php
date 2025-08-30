<?php
namespace App\Repositories\Account;

use App\Enums\AccountHeads;
use App\Models\Backend\Account;
use App\Models\User;
use App\Repositories\Account\AccountInterface;
use App\Enums\Status;
use App\Enums\UserType;
use App\Models\Backend\BankTransaction;
use Carbon\Carbon;

class AccountRepository implements AccountInterface{
    public function all(){
        return Account::with('user')->orderByDesc('id')->where(function($query){
            if(request()->date) { 
                $date = explode('To', request()->date);
                if(is_array($date)) {
                    $from   = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to     = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString(); 
                    $query->whereBetween('created_at', [$from, $to]); 
                }
            }
        })->paginate(10);
    }
    public function getAll(){
        return Account::with('user')->orderByDesc('id')->get();
    }

    public function filter($request){
        return Account::with('user')->where(function($query)use($request){
            if($request->holder_name){
                $query->where('account_holder_name', 'like', '%' . $request->holder_name . '%');
            }
            if($request->bank){
                $query->where('bank',$request->bank);
            }
            if($request->account_no):
                $query->where('account_no', 'like', '%' . $request->account_no . '%');
            endif;
        })->orderByDesc('id')->paginate(10);
    }


    public function get($id){
        return Account::find($id);
    }

    public function useraccount($id){
        return Account::where('user_id',$id)->get();
    }

    public function users(){
        return User::where('user_type', UserType::ADMIN)->with('upload')->get();

    }

    public function store($request){
        try {
            $account                           = new Account();
            if ($request->gateway == 1)
            {
                $account->type                 = $request->type;
                $account->user_id              = $request->user;
                $account->gateway              = $request->gateway;
                $account->opening_balance      = $request->balance;
                $account->balance              = $request->balance;
            }
            else if ($request->gateway == 2)
            {
                $account->type                 = $request->type;
                $account->user_id              = $request->user;
                $account->gateway              = $request->gateway;
                $account->account_holder_name  = $request->account_holder_name;
                $account->account_no           = $request->account_no;
                $account->bank                 = $request->bank;
                $account->branch_name          = $request->branch_name;
                $account->opening_balance      = $request->opening_balance;
                $account->balance              = $request->opening_balance;
            }
            else if ($request->gateway == 3 || $request->gateway == 4 || $request->gateway == 5)
            {
                $account->type                 = $request->type;
                $account->user_id              = $request->user;
                $account->gateway              = $request->gateway;
                $account->account_holder_name  = $request->account_holder_name;
                $account->account_type         = $request->account_type;
                $account->mobile               = $request->mobile;
                $account->opening_balance      = $request->opening_balance;
                $account->balance              = $request->opening_balance;
            }
            $account->status                   = $request->status;
            $account->save();
            if($account):
                $bank_transaction                   =  new BankTransaction();
                $bank_transaction->account_id       =  $account->id;
                $bank_transaction->type             =  AccountHeads::INCOME;
                $bank_transaction->amount           =  $account->balance;
                $bank_transaction->date             =  date('Y-m-d H:i:s');
                $bank_transaction->note             =  __('account.opening_balance');
                $bank_transaction->save();
            endif;
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $account                           = Account::find($id);
            // because gateway change
            $account->type                     = null;
            $account->user_id                  = null;
            $account->gateway                  = null;
            $account->balance                  = null;
            $account->account_holder_name      = null;
            $account->account_no               = null;
            $account->bank                     = null;
            $account->branch_name              = null;
            $account->opening_balance          = null;
            $account->mobile                   = null;
            $account->account_type             = null;
            if ($request->gateway == 1)
            {
                $account->type                 = $request->type;
                $account->user_id              = $request->user;
                $account->gateway              = $request->gateway;
                $account->opening_balance      = $request->balance;
                $account->balance              = $request->balance;
            }
            else if ($request->gateway == 2)
            {
                $account->type                 = $request->type;
                $account->user_id              = $request->user;
                $account->gateway              = $request->gateway;
                $account->account_holder_name  = $request->account_holder_name;
                $account->account_no           = $request->account_no;
                $account->bank                 = $request->bank;
                $account->branch_name          = $request->branch_name;
                $account->opening_balance      = $request->opening_balance;
                $account->balance              = $request->opening_balance;
            }
            else if ($request->gateway == 3 || $request->gateway == 4 || $request->gateway == 5)
            {
                $account->type                 = $request->type;
                $account->user_id              = $request->user;
                $account->gateway              = $request->gateway;
                $account->account_holder_name  = $request->account_holder_name;
                $account->account_type         = $request->account_type;
                $account->mobile               = $request->mobile;
                $account->opening_balance      = $request->opening_balance;
                $account->balance              = $request->opening_balance;
            }
            $account->status                   = $request->status;
            $account->save();
            if($account):
                $bank_transaction                   =  BankTransaction::where(['account_id'=>$id,'fund_transfer_id'=>null])->first();
                if($bank_transaction ==null):
                $bank_transaction  = new BankTransaction();
                endif;
                $bank_transaction->account_id       =  $account->id;
                $bank_transaction->type             =  AccountHeads::INCOME;
                $bank_transaction->amount           =  $account->balance;
                $bank_transaction->date             =  date('Y-m-d H:i:s');
                $bank_transaction->note             =  __('account.opening_balance');
                $bank_transaction->save();
            endif;
            return true;
        }
        catch (\Exception $e) {

            return false;
        }
    }

    public function delete($id){
        try {
            return Account::destroy($id);

        } catch (\Throwable $th) {
            return false;
        }
    }

    public function currentBalance($data){
        return Account::find($data['search']);
    }
}
