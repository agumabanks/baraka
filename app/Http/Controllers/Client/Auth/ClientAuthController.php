<?php

namespace App\Http\Controllers\Client\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClientAuthController extends Controller
{
    /**
     * Show the customer login form
     */
    public function showLoginForm()
    {
        return view('client.auth.login');
    }

    /**
     * Handle customer login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::guard('customer')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Update last login info
            $customer = Auth::guard('customer')->user();
            $customer->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            return redirect()->intended(route('client.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Show the customer registration form
     */
    public function showRegistrationForm()
    {
        return view('client.auth.register');
    }

    /**
     * Handle customer registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $customer = Customer::create([
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => $request->company_name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password, // Will be auto-hashed by model mutator
            'customer_type' => 'regular',
            'status' => 'active',
            'customer_since' => now(),
            'source' => 'website',
        ]);

        // Log in the customer
        Auth::guard('customer')->login($customer, true);

        return redirect()->route('client.dashboard')
            ->with('success', 'Welcome! Your account has been created successfully.');
    }

    /**
     * Handle customer logout
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')
            ->with('success', 'You have been logged out successfully.');
    }
}
