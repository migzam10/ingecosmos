<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'roles'             => 'array',
            'activo'            => 'boolean',
        ];
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?: []);
    }

    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($roles, $this->roles ?: []));
    }

    public function tecnico()
    {
        return $this->hasOne(Tecnico::class, 'id_user');
    }
}
