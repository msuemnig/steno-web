<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExtensionAuthController extends Controller
{
    public function show(Request $request)
    {
        if (!$request->user()) {
            return redirect()->route('login', ['redirect' => '/auth/extension-login']);
        }

        $token = $request->user()->createToken('steno-extension')->plainTextToken;

        return Inertia::render('Auth/ExtensionLogin', [
            'token' => $token,
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }

    public function generateToken(Request $request)
    {
        $token = $request->user()->createToken('steno-extension')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }
}
