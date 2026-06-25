<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WpSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WpToolkitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/wp-toolkits/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/dashboard');

        $response->assertStatus(200);
        $response->assertSee('WP Toolkits Dashboard');
    }

    public function test_dashboard_shows_no_sites_message_when_empty(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/dashboard');

        $response->assertSee('No WordPress Sites');
        $response->assertSee('Add Site');
    }

    public function test_dashboard_shows_wp_cli_status(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/dashboard');

        $response->assertSee('WP-CLI');
    }

    public function test_management_requires_authentication(): void
    {
        $response = $this->get('/wp-toolkits/management');

        $response->assertRedirect('/login');
    }

    public function test_management_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/management');

        $response->assertStatus(200);
        $response->assertSee('WP Toolkits Management');
    }

    public function test_management_shows_wp_cli_tab_by_default(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/management');

        $response->assertSee('WP-CLI Status');
        $response->assertSee('Run WP-CLI Command');
    }

    public function test_management_sites_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/management?tab=sites');

        $response->assertStatus(200);
        $response->assertSee('Add Site');
        $response->assertSee('Registered Sites');
    }

    public function test_management_core_tab_shows_site_prompt_when_no_site(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/wp-toolkits/management?tab=core');

        $response->assertStatus(200);
    }

    public function test_can_add_and_remove_site_via_management(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->post('/wp-toolkits/management', [
            'tab' => 'sites',
            'action' => 'add-site',
            'name' => 'Test Site',
            'path' => '/var/www/test',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('wp_sites', [
            'name' => 'Test Site',
            'path' => '/var/www/test',
        ]);

        $site = WpSite::where('path', '/var/www/test')->first();

        $response = $this->post('/wp-toolkits/management', [
            'tab' => 'sites',
            'action' => 'remove-site',
            'site_id' => $site->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('wp_sites', ['id' => $site->id]);
    }

    public function test_dashboard_shows_registered_sites(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        WpSite::create(['name' => 'My Blog', 'path' => '/var/www/blog']);

        $response = $this->get('/wp-toolkits/dashboard');

        $response->assertStatus(200);
        $response->assertSee('My Blog');
        $response->assertSee('/var/www/blog');
    }
}
