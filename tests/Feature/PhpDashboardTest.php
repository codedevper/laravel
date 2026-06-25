<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_dashboard_requires_authentication(): void
    {
        $response = $this->get('/php/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_php_dashboard_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/dashboard');

        $response->assertStatus(200);
        $response->assertSee('PHP Dashboard');
        $response->assertSee(PHP_VERSION);
    }

    public function test_php_dashboard_shows_php_version(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/dashboard');

        $response->assertSee(PHP_VERSION);
        $response->assertSee(PHP_SAPI);
    }

    public function test_php_dashboard_shows_expected_sections(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/dashboard');

        $response->assertSee('PHP Environment');
        $response->assertSee('Key Configuration');
        $response->assertSee('OPcache');
        $response->assertSee('Xdebug');
        $response->assertSee('Loaded Extensions');
        $response->assertSee('PHP-FPM');
        $response->assertSee('Swoole');
    }
}
