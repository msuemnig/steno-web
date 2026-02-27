<?php

namespace App\Policies;

use App\Models\Script;
use App\Models\User;

class ScriptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Script $script): bool
    {
        return $script->team->hasUser($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Script $script): bool
    {
        $role = $script->team->userRole($user);

        if (in_array($role, ['owner', 'admin'])) {
            return true;
        }

        if ($role === 'editor') {
            return $script->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Script $script): bool
    {
        return $this->update($user, $script);
    }
}
