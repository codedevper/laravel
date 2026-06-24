<?php

use App\Models\AI\AgentConversation;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Socialite;

Route::get('/socialite/{redirect}', function (string $redirect) {
    return Socialite::driver($redirect)->redirect();
});

Route::get('/socialite/{redirect}/callback', function (string $redirect) {
    $sUser = Socialite::driver($redirect)->user();

    if (! $sUser->getEmail()) {
        return redirect()->route('login')->withErrors([
            'driver' => __('Driver did not return an email address.'),
        ]);
    }
    
    $agent = AgentConversation::where('title', $redirect)->where('id', $sUser->getId())->first();

    if ($agent) {
        Auth::login($agent->user);

        return redirect()->intended(config('fortify.home'));
    }

    $user = User::firstOrCreate(
        ['email' => $sUser->getEmail()],
        [
            'name' => $sUser->getName() ?? $sUser->getNickname() ?? $sUser->getEmail(),
            'password' => str()->random(40),
        ]
    );

    $user->connections()->updateOrCreate(
        [
            'title' => $redirect,
            'id' => $sUser->getId(),
        ],
        [
            'user_id' => $user->id,
            'name' => $sUser->name,
            'email' => $sUser->getEmail(),
            'nickname' => $sUser->getNickname(),
            'avatar_url' => $sUser->getAvatar(),
            'access_token' => $sUser->token,
            'refresh_token' => $sUser->refreshToken,
            'expires_in' => $sUser->expiresIn,
        ]
    );

    if (is_null($user->current_team_id)) {
        # code...
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));
    }

    Auth::login($user);

    return redirect('/dashboard');
});