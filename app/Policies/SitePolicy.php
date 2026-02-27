<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return $site->team->hasUser($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Site $site): bool
    {
        $role = $site->team->userRole($user);
        return in_array($role, ['owner', 'admin', 'editor']);
    }

    public function delete(User $user, Site $site): bool
    {
        $role = $site->team->userRole($user);
        return in_array($role, ['owner', 'admin']);
    }
}
