<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class MailSettingController extends Controller
{
    public function index()
    {
        return view('backend.mail_settings.index');
    }

    public function update(Request $request)
    {
        try {

            setEnv('MAIL_MAILER', $request->mail_mailer);
            setEnv('MAIL_HOST', $request->mail_host);
            setEnv('MAIL_PORT', $request->mail_port);
            setEnv('MAIL_USERNAME', $request->mail_username);
            setEnv('MAIL_PASSWORD', $request->mail_password);
            setEnv('MAIL_ENCRYPTION', $request->mail_encryption);
            setEnv('MAIL_FROM_ADDRESS', $request->mail_from_address);
            setEnv('MAIL_FROM_NAME', $request->mail_from_name);
            Artisan::call('optimize:clear');

            Toastr::success('Saved successfully.', __('message.success'));

            return redirect()->back();

        } catch (\Throwable $th) {
            Toastr::error(__('income.error_msg'), __('message.error'));

            return redirect()->back();
        }
    }

    public function sendTestMail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Mail::to($request->email)->send(new TestMail);
            Toastr::success('Sended successfully.', __('message.success'));

            return redirect()->back();
        } catch (\Throwable $th) {

            Toastr::error('Invalid mail configuration', 'Error');

            return redirect()->back();
        }
    }
}
