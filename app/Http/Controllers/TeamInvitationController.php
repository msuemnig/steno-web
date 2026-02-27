<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamInvitationController extends Controller
{
    public function store(Request $request, Team $team)
    {
        abort_unless(in_array($team->userRole($request->user()), ['owner', 'admin']), 403);

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,editor,viewer'],
        ]);

        abort_if(
            $team->members()->where('email', $request->email)->exists(),
            422,
            'User is already a team member.'
        );

        // Enforce member limit based on plan
        $planKey = $team->subscribed('default') ? $team->subscription('default')->stripe_price : null;
        $maxMembers = null;
        foreach (config('steno.plans') as $plan) {
            if ($plan['price_yearly'] === $planKey && $plan['max_members']) {
                $maxMembers = $plan['max_members'];
                break;
            }
        }
        if ($maxMembers && $team->members()->count() >= $maxMembers) {
            abort(403, "Your plan allows up to {$maxMembers} team members.");
        }

        $team->invitations()->create([
            'email' => $request->email,
            'role' => $request->role,
            'token' => Str::random(64),
        ]);

        return back()->with('success', 'Invitation sent.');
    }

    public function accept(string $token)
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        $user = auth()->user();
        if (!$user) {
            session(['url.intended' => route('team-invitations.accept', $token)]);
            return redirect()->route('login');
        }

        abort_if($invitation->team->hasUser($user), 409, 'Already a member.');

        $invitation->team->members()->attach($user->id, ['role' => $invitation->role]);
        $user->switchTeam($invitation->team);
        $invitation->delete();

        return redirect()->route('teams.show', $invitation->team_id)->with('success', 'Invitation accepted.');
    }

    public function destroy(TeamInvitation $invitation)
    {
        abort_unless(
            in_array($invitation->team->userRole(request()->user()), ['owner', 'admin']),
            403
        );

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled.');
    }
}
