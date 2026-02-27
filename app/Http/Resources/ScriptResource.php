<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScriptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'persona_id' => $this->persona_id,
            'name' => $this->name,
            'url_hint' => $this->url_hint,
            'created_by_name' => $this->created_by_name,
            'fields' => $this->fields,
            'version' => $this->version,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
