<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonaResource;
use App\Models\Persona;
use Illuminate\Http\Request;

class ApiPersonaController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 403);

        return PersonaResource::collection($team->personas()->get());
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 403);

        $data = $request->validate([
            'id' => ['sometimes', 'uuid'],
            'site_id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $persona = Persona::create([
            ...$data,
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
        ]);

        return new PersonaResource($persona);
    }

    public function show(Request $request, Persona $persona)
    {
        $this->authorize('view', $persona);

        return new PersonaResource($persona);
    }

    public function update(Request $request, Persona $persona)
    {
        $this->authorize('update', $persona);

        $data = $request->validate([
            'site_id' => ['nullable', 'uuid'],
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $persona->update($data);

        return new PersonaResource($persona);
    }

    public function destroy(Request $request, Persona $persona)
    {
        $this->authorize('delete', $persona);
        $persona->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
