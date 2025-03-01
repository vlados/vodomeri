<?php

namespace App\Livewire\Resident;

use App\Models\WaterMeter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddWaterMeter extends Component
{
    public $selectedApartmentId = null;
    public $apartments = [];
    public $serialNumber = '';
    public $type = 'cold';
    public $location = '';
    public $installationDate;
    public $initialReading = 0;
    
    public function mount()
    {
        $user = Auth::user();
        
        // Get apartments associated with this user
        $this->apartments = $user->apartments;
        
        if ($this->apartments->isNotEmpty()) {
            $this->selectedApartmentId = $this->apartments->first()->id;
        }
        
        $this->installationDate = now()->format('Y-m-d');
    }
    
    public function save()
    {
        $this->validate([
            'selectedApartmentId' => 'required|exists:apartments,id',
            'serialNumber' => 'required|string|max:50|unique:water_meters,serial_number',
            'type' => 'required|in:hot,cold',
            'location' => 'nullable|string|max:255',
            'installationDate' => 'required|date|before_or_equal:today',
            'initialReading' => 'required|numeric|min:0',
        ]);
        
        // Verify the user has access to this apartment
        $userApartmentIds = Auth::user()->apartments->pluck('id')->toArray();
        if (!in_array($this->selectedApartmentId, $userApartmentIds)) {
            session()->flash('error', 'You do not have access to this apartment.');
            return;
        }
        
        // Create the water meter
        $waterMeter = new WaterMeter();
        $waterMeter->apartment_id = $this->selectedApartmentId;
        $waterMeter->serial_number = $this->serialNumber;
        $waterMeter->type = $this->type;
        $waterMeter->location = $this->location;
        $waterMeter->installation_date = $this->installationDate;
        $waterMeter->initial_reading = $this->initialReading;
        $waterMeter->status = 'pending'; // Added status for approval workflow
        $waterMeter->save();
        
        session()->flash('success', 'Water meter added successfully. It will need to be approved by an administrator.');
        return redirect()->route('dashboard');
    }
    
    public function render()
    {
        return view('livewire.resident.add-water-meter');
    }
}