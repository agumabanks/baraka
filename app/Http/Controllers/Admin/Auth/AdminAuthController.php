<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Security\LockoutManager;
use App\Events\Account\UserLoggedIn;

class AdminAuthController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function username(): string
    {
        return 'login';
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();
            
            // Fire login event
            event(new UserLoggedIn($user));
            
            // Reset failed attempts
            app(LockoutManager::class)->resetFailedAttempts($user);
            
            return redirect()->intended(route('admin.dashboard'));
        }

        // Authentication failed
        $lockoutManager = app(LockoutManager::class);
        $lockoutManager->recordFailedAttempt($this->loginIdentifier($request), $request->ip());
        
        if ($lockoutManager->shouldLock($this->loginIdentifier($request))) {
            $user = \App\Models\User::where('email', $this->loginIdentifier($request))
                ->orWhere('mobile', $this->loginIdentifier($request))
                ->first();
            if ($user) {
                $lockoutManager->lock($user);
            }
        }

        $this->incrementLoginAttempts($request);

        return back()->withErrors(['email' => trans('auth.failed')]);
    }

    public function redirectTo()
    {
        return route('admin.dashboard');
    }

    protected function guard()
    {
        return Auth::guard('web');
    }

    protected function authenticated(Request $request, $user)
    {
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    protected function credentials(Request $request)
    {
        // Not used directly; attemptLogin handles multiple identifiers.
        return ['login' => $request->get('login'), 'password' => $request->get('password'), 'status' => '1'];
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        $login = $this->loginIdentifier($request);
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $attempts = [];
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $attempts[] = ['email' => $login, 'password' => $password, 'status' => '1'];
        }
        $attempts[] = ['mobile' => $login, 'password' => $password, 'status' => '1'];

        foreach ($attempts as $creds) {
            if ($this->guard()->attempt($creds, $remember)) {
                return true;
            }
        }

        return false;
    }

    private function loginIdentifier(Request $request): string
    {
        return trim((string) $request->input('login'));
    }
}
