<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnforceApiAccessTest extends TestCase
{
    public function test_non_api_request_returns_406()
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(406);
        $response->assertJsonStructure(['message']);
    }

    public function test_health_check_is_allowed()
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
    }
}
