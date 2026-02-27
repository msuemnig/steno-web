<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;

class ApiSiteController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 403);

        return SiteResource::collection($team->sites()->get());
    }

    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;
        abort_unless($team, 403);

        $data = $request->validate([
            'id' => ['sometimes', 'uuid'],
            'hostname' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $site = Site::create([
            ...$data,
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
        ]);

        return new SiteResource($site);
    }

    public function show(Request $request, Site $site)
    {
        $this->authorize('view', $site);

        return new SiteResource($site);
    }

    public function update(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        $data = $request->validate([
            'hostname' => ['sometimes', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $site->update($data);

        return new SiteResource($site);
    }

    public function destroy(Request $request, Site $site)
    {
        $this->authorize('delete', $site);
        $site->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
