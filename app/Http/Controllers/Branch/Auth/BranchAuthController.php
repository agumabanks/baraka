<?php

namespace App\Http\Controllers\Branch\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;

class BranchAuthController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username(): string
    {
        return 'login';
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('branch.auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();
            // After successful authentication
            event(new \App\Events\Account\UserLoggedIn($user));
            // Reset failed attempts
            app(\App\Services\Security\LockoutManager::class)->resetFailedAttempts($user);
            return redirect()->intended(route('branch.dashboard'));
        }
        // Authentication failed
        $lockoutManager = app(\App\Services\Security\LockoutManager::class);
        $lockoutManager->recordFailedAttempt($request->input('email'), $request->ip());
        if ($lockoutManager->shouldLock($request->input('email'))) {
            $user = \App\Models\User::where('email', $request->input('email'))->first();
            if ($user) {
                $lockoutManager->lock($user);
            }
        }
        return back()->withErrors(['email' => trans('auth.failed')]);
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectTo()
    {
        return route('branch.dashboard');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('web');
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        return redirect()->route('branch.dashboard');
    }
    
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('branch.login');
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
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
        $login = trim((string) $request->input('login'));
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
}
