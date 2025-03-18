<?php

namespace App\Filament\Resources\ReadingResource\Pages;

use App\Filament\Resources\ReadingResource;
use App\Models\WaterMeter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReading extends CreateRecord
{
    protected static string $resource = ReadingResource::class;
    
    /**
     * Pre-fill the form with water meter ID if provided in the URL
     */
    protected function getPrefilledData(): array
    {
        $data = parent::getPrefilledData();
        
        // Check if a water meter ID was provided in the URL
        if ($this->request->has('water_meter_id')) {
            $waterMeterId = $this->request->input('water_meter_id');
            
            // Get the water meter
            $waterMeter = WaterMeter::find($waterMeterId);
            if ($waterMeter) {
                $data['water_meter_id'] = $waterMeterId;
                $data['water_meter_serial'] = $waterMeter->serial_number;
                $data['apartment_id'] = $waterMeter->apartment_id;
            }
        }
        
        return $data;
    }
    
    /**
     * Set the user ID automatically
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Make sure the user_id is set to the current user
        $data['user_id'] = auth()->id();
        
        return static::getResource()::getModel()::create($data);
    }
}
