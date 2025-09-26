<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Enums\Status;

class CustomerController extends Controller
{
    public function signUp()
    {
        return view('frontend.customer.sign_up');
    }

    public function signUpStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'terms' => 'accepted',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_e164' => $request->phone,
            'address' => $request->address,
            'user_type' => 'customer',
            'status' => Status::ACTIVE,
        ]);

        Auth::login($user);

        // Prefer customer portal for customer users if route exists
        try {
            return redirect()->route('portal.index')->with('success', 'Registration successful! Welcome to your dashboard.');
        } catch (\Throwable $e) {
            return redirect()->route('dashboard.index')->with('success', 'Registration successful! Welcome to our platform.');
        }
    }
}
