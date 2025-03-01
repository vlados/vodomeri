<?php

namespace App\Livewire\Resident;

use App\Models\Reading;
use App\Models\WaterMeter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class SubmitReading extends Component
{
    use WithFileUploads;
    
    public $meterId;
    public $waterMeter;
    public $readingDate;
    public $value;
    public $notes;
    public $photo;
    
    public $previousReading;
    public $initialReading;
    
    public function mount($meterId = null)
    {
        $this->meterId = $meterId;
        $this->readingDate = now()->format('Y-m-d');
        
        if ($this->meterId) {
            $this->loadMeterData();
        }
    }
    
    protected function loadMeterData()
    {
        $user = Auth::user();
        $apartmentIds = $user->apartments->pluck('id');
        
        $this->waterMeter = WaterMeter::whereIn('apartment_id', $apartmentIds)
            ->where('id', $this->meterId)
            ->with('apartment')
            ->first();
            
        if (!$this->waterMeter) {
            // Handle unauthorized access to meter
            session()->flash('error', __('You do not have access to this water meter.'));
            return redirect()->route('dashboard');
        }
        
        // Get previous reading for comparison
        $this->previousReading = Reading::where('water_meter_id', $this->meterId)
            ->orderBy('reading_date', 'desc')
            ->first();
            
        $this->initialReading = $this->waterMeter->initial_reading;
    }
    
    public function submit()
    {
        $this->validate([
            'readingDate' => 'required|date|before_or_equal:today',
            'value' => 'required|numeric|min:' . ($this->previousReading ? $this->previousReading->value : $this->initialReading),
            'notes' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ], [
            'value.min' => 'The reading value must be greater than or equal to the previous reading (' . 
                ($this->previousReading ? number_format($this->previousReading->value, 3) : number_format($this->initialReading, 3)) . ' m³).',
        ]);
        
        // Handle photo upload if provided
        $photoPath = null;
        if ($this->photo) {
            $photoPath = $this->photo->store('reading-photos', 'public');
        }
        
        // Create the reading
        $reading = new Reading();
        $reading->water_meter_id = $this->meterId;
        $reading->user_id = Auth::id();
        $reading->reading_date = Carbon::parse($this->readingDate);
        $reading->value = $this->value;
        $reading->notes = $this->notes;
        $reading->photo_path = $photoPath;
        $reading->save();
        
        session()->flash('success', __('Reading submitted successfully.'));
        return redirect()->route('dashboard');
    }
    
    public function render()
    {
        return view('livewire.resident.submit-reading');
    }
}
