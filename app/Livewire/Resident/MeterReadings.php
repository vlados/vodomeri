<?php

namespace App\Livewire\Resident;

use App\Models\Reading;
use App\Models\WaterMeter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MeterReadings extends Component
{
    use WithPagination;
    
    public $filter = [
        'meter_type' => '',
        'status' => '',
        'date_range' => 'all',
    ];
    
    public function mount()
    {
        // Initialize filter with defaults
    }
    
    public function updatedFilter()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $user = Auth::user();
        
        // Get all apartments associated with this user
        $apartmentIds = $user->apartments->pluck('id');
        
        // Get all water meters for these apartments
        $waterMeterIds = WaterMeter::whereIn('apartment_id', $apartmentIds)->pluck('id');
        
        // Get readings for these water meters
        $query = Reading::whereIn('water_meter_id', $waterMeterIds)
            ->with(['waterMeter.apartment', 'user'])
            ->orderByDesc('reading_date');
            
        // Apply filters
        if (!empty($this->filter['meter_type'])) {
            $query->whereHas('waterMeter', function($q) {
                $q->where('type', $this->filter['meter_type']);
            });
        }
        
        if (!empty($this->filter['status'])) {
            $query->where('status', $this->filter['status']);
        }
        
        if (!empty($this->filter['date_range'])) {
            if ($this->filter['date_range'] === 'last_month') {
                $query->whereMonth('reading_date', now()->subMonth()->month)
                      ->whereYear('reading_date', now()->subMonth()->year);
            } elseif ($this->filter['date_range'] === 'this_month') {
                $query->whereMonth('reading_date', now()->month)
                      ->whereYear('reading_date', now()->year);
            } elseif ($this->filter['date_range'] === 'last_3_months') {
                $query->where('reading_date', '>=', now()->subMonths(3));
            } elseif ($this->filter['date_range'] === 'last_6_months') {
                $query->where('reading_date', '>=', now()->subMonths(6));
            }
        }
        
        $readings = $query->paginate(10);
        
        return view('livewire.resident.meter-readings', [
            'readings' => $readings,
        ]);
    }
}
