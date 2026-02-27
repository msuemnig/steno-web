<?php

namespace Database\Factories;

use App\Models\Script;
use App\Models\Site;
use App\Models\Persona;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Script>
 */
class ScriptFactory extends Factory
{
    protected $model = Script::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'site_id' => null,
            'persona_id' => null,
            'name' => fake()->sentence(3),
            'url_hint' => fake()->url(),
            'created_by_name' => fake()->name(),
            'fields' => [
                [
                    'selector' => '#' . fake()->word(),
                    'value' => fake()->name(),
                    'type' => 'fill',
                ],
                [
                    'selector' => '#' . fake()->word(),
                    'value' => fake()->email(),
                    'type' => 'fill',
                ],
                [
                    'selector' => '.btn-submit',
                    'value' => '',
                    'type' => 'click',
                ],
            ],
            'version' => 1,
        ];
    }

    public function forSite(Site $site): static
    {
        return $this->state(fn (array $attributes) => [
            'site_id' => $site->id,
        ]);
    }

    public function forPersona(Persona $persona): static
    {
        return $this->state(fn (array $attributes) => [
            'persona_id' => $persona->id,
        ]);
    }
}
