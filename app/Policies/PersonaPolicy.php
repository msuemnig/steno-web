<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\User;

class PersonaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Persona $persona): bool
    {
        return $persona->team->hasUser($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Persona $persona): bool
    {
        $role = $persona->team->userRole($user);
        return in_array($role, ['owner', 'admin', 'editor']);
    }

    public function delete(User $user, Persona $persona): bool
    {
        $role = $persona->team->userRole($user);
        return in_array($role, ['owner', 'admin']);
    }
}
