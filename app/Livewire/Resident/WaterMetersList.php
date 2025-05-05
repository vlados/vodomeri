<?php

namespace App\Livewire\Resident;

use App\Models\WaterMeter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Livewire\Component;
use Livewire\WithPagination;

class WaterMetersList extends Component
{
    use WithPagination;

    public $search = '';

    public $typeFilter = '';
    public $meterToDelete = null;
    public $showDeleteModal = false;
    public $deleteErrorMessage = '';
    public $readingsCount = 0;

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

    /**
     * Open delete confirmation modal for a specific water meter
     */
    public function confirmDelete($meterId)
    {
        $this->meterToDelete = $meterId;
        $this->showDeleteModal = true;
        $this->deleteErrorMessage = '';
        
        // Check if meter has readings
        $user = Auth::user();
        $userApartmentIds = $user->apartments->pluck('id')->toArray();
        
        $meter = WaterMeter::where('id', $meterId)
            ->whereIn('apartment_id', $userApartmentIds)
            ->first();
            
        if ($meter) {
            $this->readingsCount = $meter->readings()->count();
        } else {
            $this->readingsCount = 0;
        }
    }
    
    /**
     * Close delete confirmation modal
     */
    public function cancelDelete()
    {
        $this->meterToDelete = null;
        $this->showDeleteModal = false;
        $this->deleteErrorMessage = '';
        $this->readingsCount = 0;
    }
    
    /**
     * Delete the selected water meter
     */
    public function deleteWaterMeter()
    {
        try {
            // Debug log
            \Log::info('Deleting water meter', [
                'meter_id' => $this->meterToDelete,
                'readings_count' => $this->readingsCount
            ]);
            
            // Make sure we have a meter ID
            if (!$this->meterToDelete) {
                $this->deleteErrorMessage = 'Няма избран водомер за изтриване.';
                return;
            }
            
            $user = Auth::user();
            $userApartmentIds = $user->apartments->pluck('id')->toArray();
            
            // Find the water meter
            $meter = WaterMeter::where('id', $this->meterToDelete)
                ->whereIn('apartment_id', $userApartmentIds)
                ->first();
                
            if (!$meter) {
                $this->deleteErrorMessage = 'Нямате достъп до този водомер или водомерът не съществува.';
                return;
            }
            
            // Check if there are readings and delete them first
            $readingsCount = $meter->readings()->count();
            
            // First delete all readings if they exist
            if ($readingsCount > 0) {
                $meter->readings()->delete();
            }
            
            // Delete the water meter
            $meter->delete();
            
            // Close the modal
            $this->meterToDelete = null;
            $this->showDeleteModal = false;
            
            // Show success message
            if ($readingsCount > 0) {
                session()->flash('success', "Водомерът и неговите {$readingsCount} показания бяха успешно изтрити.");
            } else {
                session()->flash('success', 'Водомерът беше успешно изтрит.');
            }
            
        } catch (\Exception $e) {
            $this->deleteErrorMessage = 'Възникна грешка при изтриването на водомера: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $title = 'Списък с водомери';
        View::share("title", $title);
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
            'title' => $title,
            'waterMeters' => $waterMeters,
        ]);
    }
}
