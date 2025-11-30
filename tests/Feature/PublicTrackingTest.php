<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    public function test_tracking_index_page_loads()
    {
        $response = $this->get('/tracking');

        $response->assertStatus(200);
        $response->assertSee('Track Your Shipment');
    }

    public function test_tracking_not_found_shows_error_page()
    {
        $response = $this->get('/tracking/NONEXISTENT-TRACKING-XYZ');

        $response->assertStatus(200);
        $response->assertSee('Shipment Not Found');
    }
}
