<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        } else {
            $user = DB::transaction(function () use ($googleUser) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);

                $team = Team::create([
                    'owner_id' => $user->id,
                    'name' => $user->name."'s Team",
                    'slug' => Str::slug($user->name).'-'.Str::random(6),
                    'plan_type' => 'free',
                ]);

                $team->members()->attach($user->id, ['role' => 'owner']);
                $user->update(['current_team_id' => $team->id]);

                return $user;
            });
        }

        Auth::login($user, true);

        return redirect('/dashboard');
    }
}
