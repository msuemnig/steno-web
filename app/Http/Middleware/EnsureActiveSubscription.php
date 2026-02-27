<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $team = $user->currentTeam;

        if (!$team || !$team->subscribed('default')) {
            return response()->json(['message' => 'Active subscription required.'], 403);
        }

        return $next($request);
    }
}
