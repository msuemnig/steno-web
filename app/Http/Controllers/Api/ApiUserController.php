<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiUserController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'current_team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'plan_type' => $team->plan_type,
                'subscribed' => $team->subscribed('default'),
            ] : null,
        ]);
    }
}
