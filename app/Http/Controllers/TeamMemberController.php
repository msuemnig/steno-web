<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function update(Request $request, Team $team, User $user)
    {
        abort_unless(in_array($team->userRole($request->user()), ['owner', 'admin']), 403);
        abort_if($user->id === $team->owner_id, 403, 'Cannot change owner role.');

        $request->validate([
            'role' => ['required', 'in:admin,editor,viewer'],
        ]);

        $team->members()->updateExistingPivot($user->id, ['role' => $request->role]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Team $team, User $user)
    {
        abort_unless(
            $request->user()->id === $user->id ||
            in_array($team->userRole($request->user()), ['owner', 'admin']),
            403
        );
        abort_if($user->id === $team->owner_id, 403, 'Cannot remove team owner.');

        $team->members()->detach($user->id);

        if ($user->current_team_id === $team->id) {
            $user->update(['current_team_id' => $user->personalTeam()?->id]);
        }

        return back()->with('success', 'Member removed.');
    }
}
