<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScriptResource;
use App\Models\Script;
use Illuminate\Http\Request;

class ApiScriptController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 403, 'No team selected.');

        $scripts = $team->scripts()->get();

        return ScriptResource::collection($scripts);
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 403);

        $data = $request->validate([
            'id' => ['sometimes', 'uuid'],
            'site_id' => ['nullable', 'uuid'],
            'persona_id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'url_hint' => ['nullable', 'string', 'max:2048'],
            'created_by_name' => ['nullable', 'string', 'max:255'],
            'fields' => ['required', 'array'],
            'version' => ['sometimes', 'integer'],
        ]);

        // Enforce free-tier script limit
        if (!$team->subscribed('default')) {
            $maxScripts = config('steno.free_tier.max_scripts');
            if ($maxScripts && $team->scripts()->count() >= $maxScripts) {
                abort(403, "Free plan allows up to {$maxScripts} scripts. Upgrade to save more.");
            }
        }

        $script = Script::create([
            ...$data,
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
        ]);

        return new ScriptResource($script);
    }

    public function show(Request $request, Script $script)
    {
        $this->authorize('view', $script);

        return new ScriptResource($script);
    }

    public function update(Request $request, Script $script)
    {
        $this->authorize('update', $script);

        $data = $request->validate([
            'site_id' => ['nullable', 'uuid'],
            'persona_id' => ['nullable', 'uuid'],
            'name' => ['sometimes', 'string', 'max:255'],
            'url_hint' => ['nullable', 'string', 'max:2048'],
            'created_by_name' => ['nullable', 'string', 'max:255'],
            'fields' => ['sometimes', 'array'],
            'version' => ['sometimes', 'integer'],
        ]);

        $script->update($data);

        return new ScriptResource($script);
    }

    public function destroy(Request $request, Script $script)
    {
        $this->authorize('delete', $script);
        $script->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
