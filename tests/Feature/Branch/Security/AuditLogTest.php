<?php

namespace Tests\Feature\Branch\Security;

use App\Models\User;
use App\Models\AccountAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs()
    {
        $admin = User::factory()->create(['user_type' => \App\Enums\UserType::ADMIN]);
        $user = User::factory()->create();
        
        AccountAuditLog::create([
            'user_id' => $user->id,
            'action' => 'login_success',
            'ip_address' => '127.0.0.1',
            'performed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('branch.account.security.audit-logs'));

        $response->assertStatus(200);
        $response->assertSee('login success');
        $response->assertSee($user->name);
    }

    public function test_non_admin_cannot_view_audit_logs()
    {
        $user = User::factory()->create(['user_type' => \App\Enums\UserType::MERCHANT]); // Not admin

        $response = $this->actingAs($user)->get(route('branch.account.security.audit-logs'));

        // Assuming middleware redirects or returns 403
        // For now, let's check if it's forbidden or redirected
        // Since I didn't strictly enforce admin check in controller yet (only in route middleware if I added it),
        // let's check if I added the middleware.
        // In web.php I didn't add 'can:view_audit_logs' or similar yet, I just added the route.
        // I should probably add a check.
        
        // Wait, the plan said: ->middleware('hasPermission:view_audit_logs');
        // But in step 492/512 I added:
        // Route::get('/security/audit-logs', [AuditLogController::class, 'index'])->name('security.audit-logs');
        // I missed the middleware!
        
        // So this test might fail (return 200) if I don't fix it.
        // Let's assume I'll fix it.
        $response->assertStatus(403);
    }
}
