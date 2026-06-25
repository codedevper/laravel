<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApacheSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_requires_authentication(): void
    {
        $response = $this->get('/apache/settings');

        $response->assertRedirect('/login');
    }

    public function test_settings_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/apache/settings');

        $response->assertStatus(200);
        $response->assertSee('Apache Settings');
    }

    public function test_settings_shows_vhosts_tab_by_default(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/apache/settings');

        $response->assertSee('Virtual Hosts');
        $response->assertSee('Create Virtual Host');
    }

    public function test_settings_modules_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/apache/settings?tab=modules');

        $response->assertStatus(200);
        $response->assertSee('Apache Modules');
    }

    public function test_settings_actions_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/apache/settings?tab=actions');

        $response->assertStatus(200);
        $response->assertSee('Apache Control');
        $response->assertSee('Test Configuration');
        $response->assertSee('Server Status');
    }

    public function test_settings_ssl_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/apache/settings?tab=ssl');

        $response->assertStatus(200);
        $response->assertSee('SSL Certificates');
        $response->assertSee('Generate Self-Signed Certificate');
    }

    public function test_settings_back_to_dashboard_link(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/apache/settings');

        $response->assertStatus(200);
        $response->assertSee('Back to Dashboard');
    }

    public function test_settings_test_config_action(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->post('/apache/settings', [
            'tab' => 'actions',
            'action' => 'test-config',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('apache_result');
    }

    public function test_settings_create_vhost_fails_without_name(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->post('/apache/settings', [
            'tab' => 'vhosts',
            'action' => 'create-vhost',
            'name' => '',
            'server_name' => 'test.example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('apache_result');
    }
}
