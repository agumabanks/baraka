<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\PasswordStrengthChecker;
use App\Services\Security\AuditLogger;
use App\Events\Account\PasswordChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    protected PasswordStrengthChecker $checker;
    protected AuditLogger $auditLogger;

    public function __construct(PasswordStrengthChecker $checker, AuditLogger $auditLogger)
    {
        $this->checker = $checker;
        $this->auditLogger = $auditLogger;
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $newPassword = $request->input('new_password');

        // Check strength and history
        $strength = $this->checker->checkStrength($newPassword);
        if ($strength < 70) {
            return back()->withErrors(['new_password' => 'Password is too weak.']);
        }
        if ($this->checker->isInHistory($user, $newPassword)) {
            return back()->withErrors(['new_password' => 'You cannot reuse a recent password.']);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        // Record password change in audit log
        $this->auditLogger->logPasswordChange($user);

        // Fire event
        event(new PasswordChanged($user));

        return redirect()->back()->with('status', 'Password updated successfully');
    }

    /**
     * Check password strength via API
     */
    public function checkStrength(Request $request)
    {
        $password = $request->input('password');
        $score = $this->checker->checkStrength($password);
        
        return response()->json(['score' => $score]);
    }
}
