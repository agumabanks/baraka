<?php

namespace App\Http\Controllers\Backend;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Merchant\MerchantInterface;
use App\Repositories\SocialLoginSettings\SocialLoginSettingsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    protected $merchantRepo;

    protected $repo;

    public function __construct(MerchantInterface $merchantRepo, SocialLoginSettingsInterface $repo)
    {
        $this->merchantRepo = $merchantRepo;
        $this->repo = $repo;
    }

    public function socialRedirect($social)
    {

        if ($social == 'google') {
            if (globalSettings('google_status') != Status::ACTIVE) {
                Toastr::error('Google login is not enabled.', 'error');

                return redirect()->back();
            }
            \Config([
                'services.google.client_id' => globalSettings('google_client_id'),
                'services.google.client_secret' => globalSettings('google_client_secret'),
                'services.google.redirect' => url('google/login'),
            ]);

            return Socialite::driver('google')->redirect();

        } elseif ($social == 'facebook') {

            if (globalSettings('facebook_status') != Status::ACTIVE) {
                Toastr::error('Facebook login is not enabled.', 'error');

                return redirect()->back();
            }
            \Config([
                'services.facebook.client_id' => globalSettings('facebook_client_id'),
                'services.facebook.client_secret' => globalSettings('facebook_client_secret'),
                'services.facebook.redirect' => url('facebook/login'),
            ]);

            return Socialite::driver('facebook')->redirect();
        }

        Toastr::error('parcel.error_msg', __('message.error'));

        return redirect()->back();
    }

    public function authGoogleLogin(Request $request)
    {
        try {

            \Config([
                'services.google.client_id' => globalSettings('google_client_id'),
                'services.google.client_secret' => globalSettings('google_client_secret'),
                'services.google.redirect' => url('google/login'),
            ]);

            $user = Socialite::driver('google')->user();
            $existUser = User::where('google_id', $user->id)->first();
            if ($existUser) {
                Auth::login($existUser);

                return redirect('/');
            } else {
                $merchantUser = $this->merchantRepo->socialSignupStore($user, 'google');
                if ($merchantUser) {
                    Auth::login($merchantUser);

                    return redirect('/');
                } else {
                    Toastr::error('parcel.error_msg', __('message.error'));

                    return redirect()->back();
                }
            }

        } catch (\Throwable $th) {
            Toastr::error('parcel.error_msg', __('message.error'));

            return redirect()->back();
        }
    }

    public function authFacebookLogin()
    {
        try {

            \Config([
                'services.facebook.client_id' => globalSettings('facebook_client_id'),
                'services.facebook.client_secret' => globalSettings('facebook_client_secret'),
                'services.facebook.redirect' => url('facebook/login'),
            ]);
            $user = Socialite::driver('facebook')->user();

            $existUser = User::where('facebook_id', $user->id)->first();
            if ($existUser) {
                Auth::login($existUser);

                return redirect('/');
            } else {
                $merchantUser = $this->merchantRepo->socialSignupStore($user, 'facebook');
                if ($merchantUser) {
                    Auth::login($merchantUser);

                    return redirect('/');
                } else {
                    Toastr::error('parcel.error_msg', __('message.error'));

                    return redirect()->back();
                }
            }

        } catch (\Throwable $th) {

            Toastr::error('parcel.error_msg', __('message.error'));

            return redirect()->back();
        }
    }

    public function socialLoginSettingsIndex()
    {

        return view('backend.setting.social_login_settings.index');
    }

    public function socialLoginSettingsUpdate(Request $request, $social)
    {

        if ($this->repo->update($request, $social)) {
            Toastr::success(__('parcel.settings_update_success'), __('message.success'));

            return redirect()->route('social.login.settings.index');
        } else {
            Toastr::error('parcel.error_msg', __('message.error'));

            return redirect()->back();
        }
    }
}
