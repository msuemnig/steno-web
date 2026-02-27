<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Teams/Index', [
            'teams' => $request->user()->teams()->withCount('members')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'owner_id' => $request->user()->id,
            'name' => $request->name,
            'slug' => Str::slug($request->name).'-'.Str::random(6),
            'plan_type' => 'free',
        ]);

        $team->members()->attach($request->user()->id, ['role' => 'owner']);
        $request->user()->switchTeam($team);

        return redirect()->route('teams.show', $team)->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        $this->authorizeTeamAccess($team);

        return Inertia::render('Teams/Show', [
            'team' => $team->load('owner'),
            'members' => $team->members()->get()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->pivot->role,
            ]),
            'invitations' => $team->invitations,
            'isOwner' => $team->owner_id === request()->user()->id,
        ]);
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeTeamRole($team, ['owner', 'admin']);

        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $team->update(['name' => $request->name]);

        return back()->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        $this->authorizeTeamRole($team, ['owner']);

        if ($team->isPersonal()) {
            return back()->with('error', 'Cannot delete personal team.');
        }

        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }

    public function switchTeam(Request $request, Team $team)
    {
        $this->authorizeTeamAccess($team);
        $request->user()->switchTeam($team);

        return back()->with('success', "Switched to {$team->name}.");
    }

    private function authorizeTeamAccess(Team $team): void
    {
        abort_unless($team->hasUser(request()->user()), 403);
    }

    private function authorizeTeamRole(Team $team, array $roles): void
    {
        $this->authorizeTeamAccess($team);
        abort_unless(in_array($team->userRole(request()->user()), $roles), 403);
    }
}
