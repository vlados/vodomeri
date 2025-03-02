<?php

namespace App\Livewire\Resident;

use App\Models\WaterMeter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class WaterMetersList extends Component
{
    use WithPagination;

    public $search = '';

    public $typeFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $userApartmentIds = $user->apartments->pluck('id')->toArray();

        $waterMeters = WaterMeter::whereIn('apartment_id', $userApartmentIds)
            ->when($this->search, function ($query) {
                return $query->where(function ($q) {
                    $q->where('serial_number', 'like', '%'.$this->search.'%')
                        ->orWhere('location', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->typeFilter, function ($query) {
                return $query->where('type', $this->typeFilter);
            })
            ->with(['apartment', 'latestReading'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.resident.water-meters-list', [
            'waterMeters' => $waterMeters,
        ]);
    }
}
