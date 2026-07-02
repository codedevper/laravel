<?php

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['server:read']);

        Jetstream::permissions([
            'server:create',
            'server:read',
            'server:update',
            'server:delete',
        ]);

        Jetstream::role('owner', 'Owner', [
            'server:create',
            'server:read',
            'server:update',
            'server:delete',
        ])->description('Owner users can perform any action.');

        Jetstream::role('admin', 'Administrator', [
            'server:create',
            'server:read',
            'server:update',
        ])->description('Administrator users have the ability to create, read, and update.');

        Jetstream::role('editor', 'Editor', [
            'server:read',
            'server:update',
        ])->description('Editor users have the ability to read and update.');

        Jetstream::role('support', 'Support Specialist', [
            'server:read',
        ])->description('Support specialists can read server information.');
    }
}
