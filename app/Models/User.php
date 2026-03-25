<?php

namespace App\Models;

use App\Modules\POS\Models\Sale;
use App\Modules\Warehouse\Models\StockOpname;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'workspace_id',
        'is_demo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_demo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function stockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }

    public function hasRole(string $role): bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles');
        }

        $needle = strtolower(trim($role));

        return $this->roles->contains(
            fn (Role $userRole): bool => strtolower((string) $userRole->name) === $needle
        );
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles');
        }

        $allowed = collect($roles)
            ->map(fn (string $role): string => strtolower(trim($role)))
            ->filter()
            ->unique()
            ->values();

        if ($allowed->isEmpty()) {
            return false;
        }

        return $this->roles->contains(
            fn (Role $userRole): bool => $allowed->contains(strtolower((string) $userRole->name))
        );
    }
}
