<?php

namespace Tests\Unit;

use App\Models\SurchargeRule;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dim_weight_calculation()
    {
        $svc = new RatingService;
        $this->assertEquals(6.0, $svc->dimWeightKg(30000, 5000));
    }

    public function test_surcharge_application()
    {
        SurchargeRule::create([
            'code' => 'FUEL', 'name' => 'Fuel', 'trigger' => 'fuel', 'rate_type' => 'percent', 'amount' => 10.0,
            'active_from' => now()->subDay(), 'active' => true,
        ]);
        SurchargeRule::create([
            'code' => 'REMOTE', 'name' => 'Remote', 'trigger' => 'remote_area', 'rate_type' => 'flat', 'amount' => 5.0,
            'currency' => 'USD', 'active_from' => now()->subDay(), 'active' => true,
        ]);
        $svc = new RatingService;
        $res = $svc->priceWithSurcharges(100.0, 5.0, now());
        $this->assertEquals(115.0, $res['total']);
        $this->assertCount(2, $res['applied']);
    }
}
