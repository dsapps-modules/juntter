<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaShellTest extends TestCase
{
    public function test_the_spa_shell_is_available_at_the_app_route(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_nested_spa_routes_return_the_same_shell(): void
    {
        $response = $this->get('/app/painel');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_home_route_is_available(): void
    {
        $response = $this->get('/app/home');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_login_route_is_available(): void
    {
        $response = $this->get('/app/login');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }
}
