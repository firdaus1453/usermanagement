<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * PENTING: primary key adalah user_id
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'is_active',
    ];
    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Override getAuthPassword untuk menggunakan password_hash
     *
     * Laravel by default menggunakan kolom 'password',
     * tapi kita menggunakan 'password_hash' sesuai soal.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }
    /**
     * Mutator untuk auto-hash password
     *
     * SECURITY: Cegah double hashing dengan cek prefix bcrypt
     * Jika value sudah hash (starts with $2y$), tidak di-hash ulang
     */
    public function setPasswordHashAttribute(?string $value): void
    {
        if (filled($value)) {
            $this->attributes['password_hash'] =
                str_starts_with((string) $value, '$2y$')
                ? $value
                : Hash::make($value);
        }
    }

    /**
     * Scope untuk filter user aktif
     *
     * Usage: User::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter by role
     *
     * Usage: User::role('admin')->get()
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Filament Panel Access Control
     *
     * Hanya user aktif yang bisa akses panel admin
     * Sesuai requirements: Login hanya untuk user aktif
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active === true;
    }

    /**
     * Helper: Check if user is superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Helper: Check if user is admin (superadmin or admin)
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    /**
     * Helper: Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
    /**
     * Helper: Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Get the name for Filament user menu
     */
    public function getFilamentName(): string
    {
        return $this->name ?? $this->email ?? 'Unknown User';
    }
}
