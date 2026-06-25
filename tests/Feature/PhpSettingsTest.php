<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_php_settings_requires_authentication(): void
    {
        $response = $this->get('/php/settings');

        $response->assertRedirect('/login');
    }

    public function test_php_settings_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings');

        $response->assertStatus(200);
        $response->assertSee('PHP Settings');
    }

    public function test_php_settings_shows_configuration_tab_by_default(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings');

        $response->assertSee('Configuration');
        $response->assertSee('memory_limit');
    }

    public function test_php_settings_extensions_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=extensions');

        $response->assertStatus(200);
        $response->assertSee('Installed Extensions');
        $response->assertSee('Enable');
        $response->assertSee('Disable');
        $response->assertSee('FPM:');
        $response->assertSee('CLI:');
        $response->assertSee('Apache:');
    }

    public function test_php_settings_extensions_tab_shows_version_selector(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=extensions');

        $response->assertStatus(200);
        $response->assertSee(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION);
    }

    public function test_php_settings_extensions_tab_with_specific_version(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=extensions&ext_version='.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION);

        $response->assertStatus(200);
        $response->assertSee('Installed Extensions');
    }

    public function test_php_settings_package_manager_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=package-manager');

        $response->assertStatus(200);
        $response->assertSee('Installed PHP Packages');
        $response->assertSee('Package Manager');
    }

    public function test_php_settings_ini_files_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=ini-files');

        $response->assertStatus(200);
        $response->assertSee('PHP .ini Files');
        $response->assertSee('Available Modules');
    }

    public function test_php_settings_default_version_tab(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=default-version');

        $response->assertStatus(200);
        $response->assertSee('Default PHP Version');
        $response->assertSee('Installed Versions');
    }

    public function test_php_settings_default_version_shows_current(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=default-version');

        $response->assertStatus(200);
        $response->assertSee(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION);
    }

    public function test_php_settings_default_version_shows_switch_button(): void
    {
        $this->actingAs(User::factory()->withPersonalTeam()->create());

        $response = $this->get('/php/settings?tab=default-version');

        $response->assertStatus(200);
        $response->assertSee('Switch');
    }
}
