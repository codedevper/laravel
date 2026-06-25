<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirewallSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_firewall_settings_requires_authentication(): void
    {
        $response = $this->get('/firewall/settings');

        $response->assertRedirect('/login');
    }

    public function test_firewall_settings_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/settings');

        $response->assertStatus(200);
        $response->assertSee('Firewall Settings');
    }

    public function test_firewall_settings_shows_rules_tab_by_default(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/settings');

        $response->assertSee('Add Rule');
        $response->assertSee('Delete Rule');
    }

    public function test_firewall_settings_ports_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/settings?tab=ports');

        $response->assertStatus(200);
        $response->assertSee('Quick Block/Unblock Ports');
        $response->assertSee('Custom Port Rule');
        $response->assertSee('Allow');
        $response->assertSee('Block');
    }

    public function test_firewall_settings_ufw_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/settings?tab=ufw');

        $response->assertStatus(200);
        $response->assertSee('UFW Status');
        $response->assertSee('UFW Rules');
    }

    public function test_firewall_settings_logging_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/settings?tab=logging');

        $response->assertStatus(200);
        $response->assertSee('Recent Dropped Packets');
        $response->assertSee('Kernel Logs');
    }

    public function test_firewall_settings_back_to_dashboard_link(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/firewall/settings');

        $response->assertStatus(200);
        $response->assertSee('Back to Dashboard');
    }
}
