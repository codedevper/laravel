<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirewallDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_firewall_dashboard_requires_authentication(): void
    {
        $response = $this->get('/firewall/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_firewall_dashboard_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Firewall Dashboard');
    }

    public function test_firewall_dashboard_shows_expected_sections(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Chain Policies');
        $response->assertSee('Port Status');
        $response->assertSee('Current iptables Rules');
        $response->assertSee('Listening Ports');
        $response->assertSee('Recent Drops');
    }

    public function test_firewall_dashboard_has_settings_link(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Settings');
    }
}
