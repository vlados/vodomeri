<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'floor',
        'owner_name',
        'email',
        'phone',
        'notes',
    ];

    /**
     * Get the water meters associated with this apartment
     */
    public function waterMeters(): HasMany
    {
        return $this->hasMany(WaterMeter::class);
    }

    /**
     * Get the invitations for this apartment
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get the users associated with this apartment
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
