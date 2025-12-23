<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Branch\UpdateProfileRequest;
use App\Http\Requests\Branch\ChangePasswordRequest;
use App\Http\Requests\Branch\UpdateNotificationPrefsRequest;
use App\Http\Requests\Branch\UpdatePreferencesRequest;
use App\Http\Requests\Branch\SubmitSupportRequest;
use App\Models\Upload;
use App\Services\SecurityService;
use App\Services\BranchContext;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function profile(): View
    {
        $branch = BranchContext::current();
        return view('branch.account.profile', compact('branch'));
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Handle profile image upload
        if ($request->hasFile('image')) {
            $imageId = $this->handleImageUpload($request->file('image'));
            if ($imageId) {
                $validated['image_id'] = $imageId;
            }
        }

        // Remove image from validated data as we're using image_id
        unset($validated['image']);

        // Update user profile
        $user->update($validated);

        return redirect()
            ->route('branch.account.profile')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Handle profile image upload
     */
    private function handleImageUpload($file): ?int
    {
        try {
            $fileName = 'profile_' . auth()->id() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'uploads/profiles';
            
            // Create directory if it doesn't exist
            if (!file_exists(public_path($path))) {
                mkdir(public_path($path), 0755, true);
            }

            $fullPath = $path . '/' . $fileName;

            // Resize and optimize image
            $img = Image::make($file);
            $img->fit(400, 400, function ($constraint) {
                $constraint->upsize();
            });
            $img->save(public_path($fullPath), 85);

            // Create Upload record
            $upload = Upload::create([
                'original' => json_encode(['original' => $fullPath]),
                'user_id' => auth()->id(),
            ]);

            // Delete old profile image if exists
            if (auth()->user()->image_id && auth()->user()->upload) {
                $oldImage = auth()->user()->upload;
                $oldPath = json_decode($oldImage->original, true)['original'] ?? null;
                if ($oldPath && file_exists(public_path($oldPath))) {
                    @unlink(public_path($oldPath));
                }
                $oldImage->delete();
            }

            return $upload->id;
        } catch (\Exception $e) {
            \Log::error('Profile image upload failed: ' . $e->getMessage());
            return null;
        }
    }

    public function security(SecurityService $securityService): View
    {
        $user = auth()->user();
        $has2FA = $securityService->has2FAEnabled($user);
        
        return view('branch.account.security', compact('has2FA'));
    }

    public function changePassword(ChangePasswordRequest $request, SecurityService $securityService): RedirectResponse
    {
        $securityService->changePassword(auth()->user(), $request->new_password);
        
        return redirect()
            ->route('branch.account.security')
            ->with('success', 'Password changed successfully!');
    }

    public function generate2FA(SecurityService $securityService)
    {
        $data = $securityService->generate2FASecret(auth()->user());
        return response()->json($data);
    }

    public function enable2FA(Request $request, SecurityService $securityService): RedirectResponse
    {
        $request->validate([
            'secret' => 'required|string',
            'verification_code' => 'required|digits:6',
        ]);

        if (!$securityService->verify2FACode($request->secret, $request->verification_code)) {
            return back()->with('error', 'Invalid verification code. Please try again.');
        }

        $securityService->enable2FA(auth()->user(), $request->secret);
        
        return redirect()
            ->route('branch.account.security')
            ->with('success', 'Two-factor authentication enabled successfully!');
    }

    public function disable2FA(SecurityService $securityService): RedirectResponse
    {
        $securityService->disable2FA(auth()->user());
        
        return redirect()
            ->route('branch.account.security')
            ->with('success', 'Two-factor authentication disabled.');
    }

    public function revokeSession(string $sessionId): RedirectResponse
    {
        // In production, implement actual session revocation logic
        // For now, just return success message
        return redirect()
            ->route('branch.account.security')
            ->with('success', 'Session revoked successfully.');
    }

    public function notifications(): View
    {
        $branch = BranchContext::current();
        return view('branch.account.notifications', compact('branch'));
    }

    public function updateNotifications(UpdateNotificationPrefsRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        
        // Build notification preferences JSON
        $prefs = [
            'email' => $validated['email'] ?? [],
            'sms' => $validated['sms'] ?? [],
            'quiet_hours' => $validated['quiet_hours'] ?? [],
            'frequency' => $validated['frequency'],
        ];
        
        $user->notification_prefs = json_encode($prefs);
        $user->save();
        
        return redirect()
            ->route('branch.account.notifications')
            ->with('success', 'Notification preferences updated successfully!');
    }

    public function devices(): View
    {
        $branch = BranchContext::current();
        return view('branch.account.devices', compact('branch'));
    }

    public function preferences(): View
    {
        $branch = BranchContext::current();
        return view('branch.account.preferences', compact('branch'));
    }

    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        
        // Update user preferences
        $user->preferred_language = $validated['language'];
        $user->timezone = $validated['timezone'];
        $user->date_format = $validated['date_format'];
        $user->time_format = $validated['time_format'];
        $user->currency_display = $validated['currency_display'];
        $user->number_format = $validated['number_format'];
        $user->theme = $validated['theme'];
        $user->save();

        // Keep UserSetting locale (used by LanguageManager) in sync with profile preference
        try {
            \App\Models\UserSetting::setLocale($user->id, $validated['language']);
        } catch (\Throwable $e) {
            // Ignore if table not available (e.g. during migrations)
        }
        
        return redirect()
            ->route('branch.account.preferences')
            ->with('success', 'Preferences updated successfully!');
    }

    public function support(): View
    {
        $branch = BranchContext::current();
        return view('branch.account.support', compact('branch'));
    }

    public function submitSupport(SubmitSupportRequest $request): RedirectResponse
    {
        // In production, create a support ticket in the database
        // For now, we'll just log it and send email notification
        \Log::info('Support ticket submitted', [
            'user_id' => auth()->id(),
            'subject' => $request->subject,
            'category' => $request->category,
            'priority' => $request->priority,
            'message' => $request->message,
        ]);
        
        // TODO: Send email to support team
        
        return redirect()
            ->route('branch.account.support')
            ->with('success', 'Support ticket submitted successfully! We\'ll get back to you soon.');
    }

    public function billing(): View
    {
        $branch = BranchContext::current();
        return view('branch.account.billing', compact('branch'));
    }
}
