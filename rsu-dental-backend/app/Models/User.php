<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'username', 'password', 'role', 'status'];

    // never sent to the frontend
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', // a plain password is hashed automatically when saved
            'role' => Role::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}