<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Settings/Show', [
            'user' => $request->user(),
            'twoFactorEnabled' => !is_null($request->user()->two_factor_confirmed_at),
            'tokens' => $request->user()->tokens->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'last_used_at' => $t->last_used_at,
                'created_at' => $t->created_at,
            ]),
        ]);
    }
}
