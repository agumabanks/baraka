<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_route_available(): void
    {
        $this->get(route('portal.index'))->assertStatus(200);
    }
}
