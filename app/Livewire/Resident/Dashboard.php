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
        
        // Get all water meters for these apartments that are approved or don't have a status field yet
        $waterMeters = WaterMeter::whereIn('apartment_id', $apartments->pluck('id'))
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhereNull('status'); // For backward compatibility with existing records
            })
            ->with(['apartment', 'latestReading'])
            ->get();
            
        // Get pending meters for notification
        $pendingMeters = WaterMeter::whereIn('apartment_id', $apartments->pluck('id'))
            ->where('status', 'pending')
            ->count();
            
        // Group water meters by apartment
        $metersByApartment = $waterMeters->groupBy('apartment_id');
        
        return view('livewire.resident.dashboard', [
            'apartments' => $apartments,
            'metersByApartment' => $metersByApartment,
            'pendingMeters' => $pendingMeters,
        ]);
    }
}
