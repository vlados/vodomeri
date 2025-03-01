<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterMeter extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'apartment_id',
        'serial_number',
        'type',
        'location',
        'installation_date',
        'initial_reading',
        'status',
    ];
    
    protected $casts = [
        'installation_date' => 'date',
        'initial_reading' => 'decimal:3',
    ];
    
    /**
     * Get the apartment this water meter belongs to
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
    
    /**
     * Get the readings for this water meter
     */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }
    
    /**
     * Get the latest reading for this water meter
     */
    public function latestReading()
    {
        return $this->hasOne(Reading::class)->latestOfMany();
    }
}
