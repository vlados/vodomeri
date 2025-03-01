<?php

namespace App\Livewire\Resident;

use App\Models\Apartment;
use App\Models\WaterMeter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        
        // Get apartments associated with this user
        $apartments = $user->apartments;
        
        // Get all water meters for these apartments
        $waterMeters = WaterMeter::whereIn('apartment_id', $apartments->pluck('id'))
            ->with(['apartment', 'latestReading'])
            ->get();
            
        // Group water meters by apartment
        $metersByApartment = $waterMeters->groupBy('apartment_id');
        
        return view('livewire.resident.dashboard', [
            'apartments' => $apartments,
            'metersByApartment' => $metersByApartment,
        ]);
    }
}
