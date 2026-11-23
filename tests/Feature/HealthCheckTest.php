<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_application_returns_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_health_check_endpoint(): void
    {
        $response = $this->get('/health');
        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }
}
