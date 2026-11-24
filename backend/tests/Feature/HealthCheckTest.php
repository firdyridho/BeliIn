<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_api_health_endpoint_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'BeliIn API v1 is healthy',
                'data' => [
                    'app' => 'BeliIn',
                    'version' => '1.0.0',
                    'status' => 'ok',
                ],
            ]);
    }
}
