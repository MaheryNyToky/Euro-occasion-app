<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_ok_with_database_and_redis(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertHeader('X-Request-Id')
            ->assertJson([
                'status' => 'ok',
                'services' => [
                    'database' => [
                        'status' => 'connected',
                    ],
                    'redis' => [
                        'status' => 'connected',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('request_id'));
        $this->assertNotEmpty($response->json('timestamp'));
    }
}
