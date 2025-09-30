<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateRequest;
use App\Repositories\Profile\ProfileInterface;
use Brian2694\Toastr\Facades\Toastr;

class ProfileController extends Controller
{
    protected $repo;

    public function __construct(ProfileInterface $repo)
    {
        $this->repo = $repo;
    }

    public function view($id)
    {
        if ($id != auth()->user()->id) {
            abort(404);
        }
        $user = $this->repo->get($id);

        return view('backend.profile.index', compact('user'));
    }

    public function create($id)
    {
        $user = $this->repo->get($id);

        return view('backend.profile.update', compact('user'));
    }

    public function changePassword($id)
    {

        if (env('DEMO') && auth()->user()->id == 1) {
            Toastr::error('Update system is disable for the demo mode.', 'Error');

            return redirect()->back();
        }

        $user = $this->repo->get($id);

        return view('backend.profile.change_password', compact('user'));
    }

    public function update($id, UpdateRequest $request)
    {
        if ($this->repo->update($id, $request)) {
            Toastr::success('Profile updated successfully.', __('message.success'));

            return redirect()->route('profile.index', $id);
        } else {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }

    public function updatePassword($id, UpdatePasswordRequest $request)
    {
        $result = $this->repo->updatePassword($id, $request);
        if ($result == 1) {
            Toastr::success('Password updated successfully', __('message.success'));

            return redirect()->route('profile.index', $id);
        } elseif ($result == 0) {
            Toastr::warning('Old password not match!', __('message.warning'));

            return redirect()->back()->withInput();
        } else {
            Toastr::error('Something went wrong.', __('message.error'));

            return redirect()->back();
        }
    }
}
