<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
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
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Get the apartments associated with this user
     */
    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the readings submitted by this user
     */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }
    
    /**
     * Check if the user can impersonate other users
     * Only users with the admin role can impersonate
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if the user can be impersonated
     * Admin users cannot be impersonated
     */
    public function canBeImpersonated(): bool
    {
        return !$this->hasRole('admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');       
    }
}
