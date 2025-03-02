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
        'date_range' => 'all',
        'reading_date' => '',
    ];

    public $sortField = 'reading_date';

    public $sortDirection = 'desc';

    public function mount()
    {
        // Initialize filter with defaults
        $this->filter['reading_date'] = '';
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function updatedFilterDateRange($value)
    {
        if (! empty($value) && $value !== 'all') {
            // If we select a predefined date range, clear the specific reading date
            $this->filter['reading_date'] = '';
        }
        $this->resetPage();
    }

    public function updatedFilterReadingDate($value)
    {
        if (! empty($value)) {
            // If we select a specific reading date, clear the predefined date range
            $this->filter['date_range'] = 'all';
        }
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // No longer needed - remove date range functionality
    public function getAvailableDatesProperty()
    {
        $user = Auth::user();
        $apartmentIds = $user->apartments->pluck('id');
        $waterMeterIds = WaterMeter::whereIn('apartment_id', $apartmentIds)->pluck('id');

        return Reading::whereIn('water_meter_id', $waterMeterIds)
            ->select('reading_date')
            ->distinct()
            ->orderBy('reading_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->reading_date->format('Y-m-d'),
                    'label' => $item->reading_date->format('d.m.Y'),
                ];
            });
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
            ->with(['waterMeter.apartment', 'user']);

        // Apply filters
        if (! empty($this->filter['meter_type'])) {
            $query->whereHas('waterMeter', function ($q) {
                $q->where('type', $this->filter['meter_type']);
            });
        }

        if (! empty($this->filter['date_range'])) {
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

        // Filter by specific reading date if selected
        if (! empty($this->filter['reading_date'])) {
            $query->whereDate('reading_date', $this->filter['reading_date']);
        }

        // Apply sorting
        if ($this->sortField === 'serial_number') {
            $query->join('water_meters', 'readings.water_meter_id', '=', 'water_meters.id')
                ->orderBy('water_meters.serial_number', $this->sortDirection)
                ->select('readings.*');
        } elseif ($this->sortField === 'meter_type') {
            $query->join('water_meters', 'readings.water_meter_id', '=', 'water_meters.id')
                ->orderBy('water_meters.type', $this->sortDirection)
                ->select('readings.*');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $readings = $query->paginate(10);

        return view('livewire.resident.meter-readings', [
            'readings' => $readings,
            'availableDates' => $this->availableDates,
        ]);
    }
}
