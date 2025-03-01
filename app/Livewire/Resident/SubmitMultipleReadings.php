<?php

namespace App\Livewire\Resident;

use App\Models\Reading;
use App\Models\WaterMeter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmitMultipleReadings extends Component
{
    use WithFileUploads;
    
    public $readingDate;
    public $meters = [];
    public $selectedApartmentId = null;
    public $apartments = [];
    
    public function mount()
    {
        $this->readingDate = now()->format('Y-m-d');
        $user = Auth::user();
        
        // Get apartments associated with this user
        $this->apartments = $user->apartments;
        
        if ($this->apartments->isNotEmpty()) {
            $this->selectedApartmentId = $this->apartments->first()->id;
            $this->loadMeters();
        }
    }
    
    public function loadMeters()
    {
        if (!$this->selectedApartmentId) {
            return;
        }
        
        $waterMeters = WaterMeter::where('apartment_id', $this->selectedApartmentId)
            ->with(['latestReading'])
            ->get();
            
        $this->meters = [];
        
        foreach ($waterMeters as $meter) {
            $previousReading = $meter->latestReading;
            
            $this->meters[] = [
                'id' => $meter->id,
                'type' => $meter->type,
                'serial_number' => $meter->serial_number,
                'location' => $meter->location,
                'value' => '',
                'photo' => null,
                'previous_value' => $previousReading ? $previousReading->value : $meter->initial_reading,
                'previous_date' => $previousReading ? $previousReading->reading_date->format('M d, Y') : 'Initial',
                'initial_reading' => $meter->initial_reading,
            ];
        }
    }
    
    public function updatedSelectedApartmentId()
    {
        $this->loadMeters();
    }
    
    public function submit()
    {
        // Validate date
        $this->validate([
            'readingDate' => 'required|date|before_or_equal:today',
        ]);
        
        // Validate each meter reading
        $validationRules = [];
        $validationMessages = [];
        
        foreach ($this->meters as $index => $meter) {
            if (!empty($meter['value'])) {
                $validationRules["meters.{$index}.value"] = "required|numeric|min:{$meter['previous_value']}";
                $validationMessages["meters.{$index}.value.min"] = "The reading must be greater than or equal to the previous reading ({$meter['previous_value']} m³).";
            }
            
            if (!empty($meter['photo'])) {
                $validationRules["meters.{$index}.photo"] = "nullable|image|max:5120";
            }
        }
        
        $this->validate($validationRules, $validationMessages);
        
        // Save readings for each meter that has a value
        $readingsSubmitted = 0;
        
        foreach ($this->meters as $meterData) {
            if (empty($meterData['value'])) {
                continue;
            }
            
            // Handle photo upload if provided
            $photoPath = null;
            if (!empty($meterData['photo'])) {
                $photoPath = $meterData['photo']->store('reading-photos', 'public');
            }
            
            // Create the reading
            $reading = new Reading();
            $reading->water_meter_id = $meterData['id'];
            $reading->user_id = Auth::id();
            $reading->reading_date = Carbon::parse($this->readingDate);
            $reading->value = $meterData['value'];
            $reading->notes = $meterData['notes'] ?? null;
            $reading->photo_path = $photoPath;
            $reading->save();
            
            $readingsSubmitted++;
        }
        
        if ($readingsSubmitted > 0) {
            session()->flash('success', "{$readingsSubmitted} " . __('readings submitted successfully.'));
        } else {
            session()->flash('error', __('No readings were submitted. Please enter at least one reading value.'));
            return;
        }
        
        return redirect()->route('dashboard');
    }
    
    public function render()
    {
        return view('livewire.resident.submit-multiple-readings');
    }
}