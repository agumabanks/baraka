<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Services\SmsService;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Security\LockoutManager;
use App\Events\Account\UserLoggedIn;
use App\Models\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    // Auth login
    public function login(Request $request)
    {
        // Use Laravel's built-in remember_token instead of storing credentials in cookies
        $this->validateLogin($request);

        // Check if account is locked via LockoutManager
        $lockoutManager = app(LockoutManager::class);
        $user = User::where('email', $request->email)->first();
        
        if ($user && $lockoutManager->isLocked($user)) {
            $info = $lockoutManager->getLockoutInfo($user);
            return back()->withErrors(['email' => $info['message']]);
        }

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {

            // login security
            if (auth()->user()->status == Status::ACTIVE && auth()->user()->verification_status == Status::INACTIVE) {
                session([
                    'otp' => auth()->user()->otp,
                    'mobile' => auth()->user()->mobile,
                    'password' => $request->password,
                ]);
                $response = app(SmsService::class)->sendOtp(auth()->user()->mobile, auth()->user()->otp);
                auth()->logout();

                return redirect()->route('merchant.otp-verification-form');
            }
            // end login security

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        // Record failed attempt via LockoutManager
        if ($user) {
            $lockoutManager->recordFailedAttempt($user->email, $request->ip());
            if ($lockoutManager->shouldLock($user->email)) {
                $lockoutManager->lock($user);
            }
        }

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
    * Post-auth redirect hook to route users by role/permission.
    */
    protected function authenticated(Request $request, $user)
    {
        // Fire login event
        event(new UserLoggedIn($user));

        // Reset failed attempts
        app(LockoutManager::class)->resetFailedAttempts($user);

        if (method_exists($user, 'hasPermission') && $user->hasPermission(['branch_manage', 'branch_read'])) {
            return redirect()->route('branch.dashboard');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole(['branch_manager', 'branch_ops_manager', 'operations_admin'])) {
            return redirect()->route('branch.dashboard');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Determine where to redirect users after login.
     */
    protected function redirectTo()
    {
        $user = Auth::user();
        if (! $user) {
            return RouteServiceProvider::HOME;
        }

        // Branch operators/managers land on branch control center
        if (method_exists($user, 'hasRole') && $user->hasRole(['branch_manager', 'branch_ops_manager', 'operations_admin'])) {
            return route('branch.dashboard');
        }

        // Staff/admin to admin dashboard
        if (method_exists($user, 'hasRole') && $user->hasRole([
            'hq_admin', 'admin', 'super-admin', 'branch_attendant', 'support', 'finance', 'driver',
        ])) {
            return RouteServiceProvider::HOME;
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission(['branch_read', 'branch_manage'])) {
            return route('branch.dashboard');
        }

        // Non-staff (clients/merchants/customers) to client portal
        return route('portal.index');
    }

    protected function credentials(Request $request)
    {
        if (is_numeric($request->get('email'))) {
            return ['mobile' => $request->get('email'), 'password' => $request->get('password'), 'status' => '1'];
        }

        return ['email' => $request->get('email'), 'password' => $request->get('password'), 'status' => '1'];
    }
}
