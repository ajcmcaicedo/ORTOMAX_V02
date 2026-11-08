<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles asignados al usuario.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
 * Verifica si el usuario tiene un permiso.
 */
public function tienePermiso(string $permiso): bool
{
    return $this->roles()
        ->whereHas('permissions', function ($query) use ($permiso) {
            $query->where('nombre', $permiso);
        })
        ->exists();
}

/**
 * Verifica si el usuario tiene un rol.
 */
public function tieneRol(string $rol): bool
{
    return $this->roles()
        ->where('nombre', $rol)
        ->exists();
}
}