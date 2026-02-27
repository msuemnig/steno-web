<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (!$team) {
            $team = $request->user()->personalTeam();
        }

        $sites = $team ? $team->sites()->withTrashed()->with(['personas' => fn ($q) => $q->withTrashed(), 'scripts' => fn ($q) => $q->withTrashed()])->get() : collect();
        $scripts = $team ? $team->scripts()->withTrashed()->get() : collect();
        $personas = $team ? $team->personas()->withTrashed()->with('site')->get() : collect();

        return Inertia::render('Dashboard/Index', [
            'sites' => $sites,
            'scripts' => $scripts,
            'personas' => $personas,
            'stats' => [
                'total_scripts' => $scripts->whereNull('deleted_at')->count(),
                'total_sites' => $sites->whereNull('deleted_at')->count(),
                'total_personas' => $personas->whereNull('deleted_at')->count(),
            ],
        ]);
    }
}
