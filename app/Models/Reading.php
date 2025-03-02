<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends Model
{
    use HasFactory;

    protected $fillable = [
        'water_meter_id',
        'user_id',
        'reading_date',
        'value',
        'consumption',
        'photo_path',
        'notes',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'value' => 'decimal:3',
        'consumption' => 'decimal:3',
    ];

    /**
     * Get the water meter this reading belongs to
     */
    public function waterMeter(): BelongsTo
    {
        return $this->belongsTo(WaterMeter::class);
    }

    /**
     * Get the user who submitted this reading
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate consumption based on previous reading
     */
    protected static function booted()
    {
        static::creating(function (Reading $reading) {
            // Get the previous reading
            $previousReading = Reading::where('water_meter_id', $reading->water_meter_id)
                ->where('reading_date', '<', $reading->reading_date)
                ->orderBy('reading_date', 'desc')
                ->first();

            // If there's no previous reading, use the initial reading from the water meter
            if (! $previousReading) {
                $waterMeter = WaterMeter::find($reading->water_meter_id);
                $previousValue = $waterMeter->initial_reading;
            } else {
                $previousValue = $previousReading->value;
            }

            // Calculate consumption
            $reading->consumption = $reading->value - $previousValue;
        });
    }
}
