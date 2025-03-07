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
     * Calculate consumption based on previous reading and handle photo paths
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
            
            // Format photo path if needed and if it's not already formatted correctly
            $reading->formatPhotoPathIfNeeded();
        });
    }
    
    /**
     * Format photo path using apartment/year/month/serial_number_timestamp.extension
     */
    public function formatPhotoPathIfNeeded()
    {
        // Skip if no photo path or already formatted correctly
        if (!$this->photo_path || !$this->water_meter_id) {
            return;
        }
        
        // Skip if photo path already follows our format
        if (strpos($this->photo_path, '/reading-photos/') === 0 && 
            preg_match('#/\d+/\d{4}/\d{2}/#', $this->photo_path)) {
            return;
        }
        
        // Get the necessary information for new path
        $waterMeter = $this->waterMeter;
        if (!$waterMeter) {
            return;
        }
        
        $apartmentId = $waterMeter->apartment_id;
        $serialNumber = $waterMeter->serial_number;
        $year = $this->reading_date->format('Y');
        $month = $this->reading_date->format('m');
        $timestamp = now()->format('Ymd_His');
        
        // Get file extension from current path
        $extension = pathinfo($this->photo_path, PATHINFO_EXTENSION);
        if (!$extension) {
            $extension = 'jpg'; // Default to jpg if no extension found
        }
        
        // Create new formatted path
        $newBasePath = "reading-photos/{$apartmentId}/{$year}/{$month}";
        $newFileName = "{$serialNumber}_{$timestamp}.{$extension}";
        $newPath = "{$newBasePath}/{$newFileName}";
        
        // Check if the file exists in public storage
        if (\Storage::disk('public')->exists($this->photo_path)) {
            // Create directory if it doesn't exist
            \Storage::disk('public')->makeDirectory($newBasePath);
            
            // Move the file to the new location
            \Storage::disk('public')->copy($this->photo_path, $newPath);
            
            // Delete the old file if it's in a temp directory or reading-photos directory
            // but only if the copy was successful
            if (\Storage::disk('public')->exists($newPath) && 
                (strpos($this->photo_path, 'reading-photos-temp/') === 0 ||
                 strpos($this->photo_path, 'reading-photos/') === 0)) {
                \Storage::disk('public')->delete($this->photo_path);
            }
            
            // Update the photo path
            $this->photo_path = $newPath;
        }
    }
    
    /**
     * Handle an uploaded file and store it in the proper path structure
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string The path where the file was stored
     */
    public static function storeUploadedPhoto($file, $waterId, $readingDate)
    {
        // Get the water meter
        $waterMeter = WaterMeter::find($waterId);
        if (!$waterMeter) {
            return $file->store('reading-photos-temp', 'public');
        }
        
        // Format the path
        $apartmentId = $waterMeter->apartment_id;
        $serialNumber = $waterMeter->serial_number;
        $year = date('Y', strtotime($readingDate));
        $month = date('m', strtotime($readingDate));
        $timestamp = now()->format('Ymd_His');
        $extension = $file->getClientOriginalExtension();
        
        // Store the file in the proper path
        return $file->storeAs(
            "reading-photos/{$apartmentId}/{$year}/{$month}",
            "{$serialNumber}_{$timestamp}.{$extension}",
            'public'
        );
    }
}
