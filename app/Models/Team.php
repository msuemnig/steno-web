<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Team extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'plan_type',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(Script::class);
    }

    public function isPersonal(): bool
    {
        return $this->members()->count() <= 1 && $this->plan_type === 'free';
    }

    public function hasUser(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function userRole(User $user): ?string
    {
        return $this->members()->where('user_id', $user->id)->first()?->pivot->role;
    }
}
