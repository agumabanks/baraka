<?php

namespace Tests\Feature\Branch;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_billing_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.billing'));

        $response->assertStatus(200);
        $response->assertViewIs('branch.account.billing');
    }

    /** @test */
    public function billing_page_displays_current_plan()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.billing'));

        $response->assertStatus(200);
        $response->assertSee('Current Plan');
        $response->assertSee('Professional Plan');
        $response->assertSee('$299');
    }

    /** @test */
    public function billing_page_displays_usage_metrics()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.billing'));

        $response->assertStatus(200);
        $response->assertSee('Usage This Month');
        $response->assertSee('Shipments Processed');
        $response->assertSee('Active Staff');
        $response->assertSee('Storage Used');
        $response->assertSee('API Calls');
    }

    /** @test */
    public function billing_page_displays_payment_method()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.billing'));

        $response->assertStatus(200);
        $response->assertSee('Payment Method');
        $response->assertSee('Company Card');
    }

    /** @test */
    public function billing_page_displays_invoice_history()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.billing'));

        $response->assertStatus(200);
        $response->assertSee('Invoice History');
        $response->assertSee('INV-');
    }

    /** @test */
    public function billing_page_displays_billing_contact()
    {
        $response = $this->actingAs($this->user)
            ->get(route('branch.account.billing'));

        $response->assertStatus(200);
        $response->assertSee('Billing Questions');
        $response->assertSee('billing@baraka.co');
    }

    /** @test */
    public function guest_cannot_access_billing_page()
    {
        $response = $this->get(route('branch.account.billing'));

        $response->assertRedirect(route('login'));
    }
}
