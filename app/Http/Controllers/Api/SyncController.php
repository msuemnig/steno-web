<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Script;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    public function sync(Request $request)
    {
        $request->validate([
            'last_synced_at' => ['nullable', 'date'],
            'sites' => ['nullable', 'array'],
            'sites.*.id' => ['required', 'uuid'],
            'sites.*.hostname' => ['required', 'string'],
            'sites.*.label' => ['nullable', 'string'],
            'sites.*.updated_at' => ['required', 'date'],
            'sites.*.deleted_at' => ['nullable', 'date'],
            'personas' => ['nullable', 'array'],
            'personas.*.id' => ['required', 'uuid'],
            'personas.*.site_id' => ['nullable', 'uuid'],
            'personas.*.name' => ['required', 'string'],
            'personas.*.updated_at' => ['required', 'date'],
            'personas.*.deleted_at' => ['nullable', 'date'],
            'scripts' => ['nullable', 'array'],
            'scripts.*.id' => ['required', 'uuid'],
            'scripts.*.site_id' => ['nullable', 'uuid'],
            'scripts.*.persona_id' => ['nullable', 'uuid'],
            'scripts.*.name' => ['required', 'string'],
            'scripts.*.url_hint' => ['nullable', 'string'],
            'scripts.*.created_by_name' => ['nullable', 'string'],
            'scripts.*.fields' => ['required', 'array'],
            'scripts.*.version' => ['sometimes', 'integer'],
            'scripts.*.updated_at' => ['required', 'date'],
            'scripts.*.deleted_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $team = $user->currentTeam;
        abort_unless($team, 403);

        $lastSyncedAt = $request->last_synced_at ? Carbon::parse($request->last_synced_at) : null;

        // Process incoming data (last-write-wins)
        $this->processSites($request->sites ?? [], $team, $user);
        $this->processPersonas($request->personas ?? [], $team, $user);
        $this->processScripts($request->scripts ?? [], $team, $user);

        // Return changes since last sync
        $query = fn ($model) => $lastSyncedAt
            ? $model->withTrashed()->where('updated_at', '>', $lastSyncedAt)
            : $model->withTrashed();

        $serverSites = $query($team->sites())->get();
        $serverPersonas = $query($team->personas())->get();
        $serverScripts = $query($team->scripts())->get();

        return response()->json([
            'synced_at' => now()->toIso8601String(),
            'sites' => $serverSites->map(fn ($s) => [
                'id' => $s->id,
                'hostname' => $s->hostname,
                'label' => $s->label,
                'created_at' => $s->created_at->toIso8601String(),
                'updated_at' => $s->updated_at->toIso8601String(),
                'deleted_at' => $s->deleted_at?->toIso8601String(),
            ]),
            'personas' => $serverPersonas->map(fn ($p) => [
                'id' => $p->id,
                'site_id' => $p->site_id,
                'name' => $p->name,
                'created_at' => $p->created_at->toIso8601String(),
                'updated_at' => $p->updated_at->toIso8601String(),
                'deleted_at' => $p->deleted_at?->toIso8601String(),
            ]),
            'scripts' => $serverScripts->map(fn ($s) => [
                'id' => $s->id,
                'site_id' => $s->site_id,
                'persona_id' => $s->persona_id,
                'name' => $s->name,
                'url_hint' => $s->url_hint,
                'created_by_name' => $s->created_by_name,
                'fields' => $s->fields,
                'version' => $s->version,
                'created_at' => $s->created_at->toIso8601String(),
                'updated_at' => $s->updated_at->toIso8601String(),
                'deleted_at' => $s->deleted_at?->toIso8601String(),
            ]),
        ]);
    }

    private function processSites(array $sites, $team, $user): void
    {
        foreach ($sites as $data) {
            $existing = Site::withTrashed()->find($data['id']);

            if ($existing && Carbon::parse($data['updated_at'])->gt($existing->updated_at)) {
                if (!empty($data['deleted_at'])) {
                    $existing->delete();
                } else {
                    $existing->restore();
                    $existing->update([
                        'hostname' => $data['hostname'],
                        'label' => $data['label'] ?? null,
                    ]);
                }
            } elseif (!$existing) {
                $site = Site::create([
                    'id' => $data['id'],
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'hostname' => $data['hostname'],
                    'label' => $data['label'] ?? null,
                ]);
                if (!empty($data['deleted_at'])) {
                    $site->delete();
                }
            }
        }
    }

    private function processPersonas(array $personas, $team, $user): void
    {
        foreach ($personas as $data) {
            $existing = Persona::withTrashed()->find($data['id']);

            if ($existing && Carbon::parse($data['updated_at'])->gt($existing->updated_at)) {
                if (!empty($data['deleted_at'])) {
                    $existing->delete();
                } else {
                    $existing->restore();
                    $existing->update([
                        'site_id' => $data['site_id'] ?? null,
                        'name' => $data['name'],
                    ]);
                }
            } elseif (!$existing) {
                $persona = Persona::create([
                    'id' => $data['id'],
                    'site_id' => $data['site_id'] ?? null,
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'name' => $data['name'],
                ]);
                if (!empty($data['deleted_at'])) {
                    $persona->delete();
                }
            }
        }
    }

    private function processScripts(array $scripts, $team, $user): void
    {
        foreach ($scripts as $data) {
            $existing = Script::withTrashed()->find($data['id']);

            if ($existing && Carbon::parse($data['updated_at'])->gt($existing->updated_at)) {
                if (!empty($data['deleted_at'])) {
                    $existing->delete();
                } else {
                    $existing->restore();
                    $existing->update([
                        'site_id' => $data['site_id'] ?? null,
                        'persona_id' => $data['persona_id'] ?? null,
                        'name' => $data['name'],
                        'url_hint' => $data['url_hint'] ?? null,
                        'created_by_name' => $data['created_by_name'] ?? null,
                        'fields' => $data['fields'],
                        'version' => $data['version'] ?? 1,
                    ]);
                }
            } elseif (!$existing) {
                $script = Script::create([
                    'id' => $data['id'],
                    'site_id' => $data['site_id'] ?? null,
                    'persona_id' => $data['persona_id'] ?? null,
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'url_hint' => $data['url_hint'] ?? null,
                    'created_by_name' => $data['created_by_name'] ?? null,
                    'fields' => $data['fields'],
                    'version' => $data['version'] ?? 1,
                ]);
                if (!empty($data['deleted_at'])) {
                    $script->delete();
                }
            }
        }
    }
}
