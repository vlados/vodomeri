<?php

namespace App\Livewire\Resident;

use App\Models\Reading;
use App\Models\WaterMeter;
use App\Services\OpenAIService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BulkReadingsUpload extends Component
{
    use WithFileUploads;

    public $readingDate;
    public $photos = [];
    public $selectedApartmentId = null;
    public array|Collection $apartments = [];
    public $meters = [];
    public $extractionResults = [];
    public $isProcessing = false;
    public $processingComplete = false;
    public $recognizedMeters = [];
    
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
                'previous_value' => $previousReading ? $previousReading->value : $meter->initial_reading,
                'previous_date' => $previousReading ? $previousReading->reading_date->format('M d, Y') : 'Initial',
                'initial_reading' => $meter->initial_reading,
                'photo_path' => null,
                'is_recognized' => false
            ];
        }

        // Reset results
        $this->extractionResults = [];
        $this->recognizedMeters = [];
        $this->processingComplete = false;
    }

    public function updatedSelectedApartmentId()
    {
        $this->loadMeters();
    }

    public function processPhotos()
    {
        $this->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:5120',
            'readingDate' => 'required|date|before_or_equal:today',
        ]);

        $this->isProcessing = true;
        $this->extractionResults = [];
        $this->recognizedMeters = [];
        
        try {
            // Store photos temporarily
            $photoPaths = [];
            foreach ($this->photos as $photo) {
                // Store in apartment-specific folder
                $apartmentId = $this->selectedApartmentId;
                $path = $photo->store("readings-bulk-temp/apartment-{$apartmentId}", 'public');
                $photoPaths[] = $path;
            }
            
            // Extract readings from all photos
            $openAIService = new OpenAIService();
            $this->extractionResults = $openAIService->extractMultipleReadings($photoPaths, $this->meters);
            
            if ($this->extractionResults['success']) {
                // Process results
                foreach ($this->extractionResults['results'] as $result) {
                    $meterId = $result['meter_id'];
                    
                    // Find the meter in our array
                    foreach ($this->meters as $index => $meter) {
                        if ($meter['id'] == $meterId) {
                            // Update the meter with the extracted value
                            // Ensure the reading is treated as an integer
                            $reading = $result['extracted_reading'];
                            // Remove any non-digit characters and convert to int
                            $reading = preg_replace('/[^0-9]/', '', $reading);
                            
                            $this->meters[$index]['value'] = $reading;
                            $this->meters[$index]['photo_path'] = $result['image_path'];
                            $this->meters[$index]['is_recognized'] = true;
                            $this->recognizedMeters[] = $index;
                            break;
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Грешка при обработката на снимките: ' . $e->getMessage());
        }
        
        $this->isProcessing = false;
        $this->processingComplete = true;
    }
    
    public function updateReading($index, $value)
    {
        $this->meters[$index]['value'] = $value;
    }
    
    public function resetForm()
    {
        $this->photos = [];
        $this->extractionResults = [];
        $this->recognizedMeters = [];
        $this->processingComplete = false;
        $this->loadMeters();
    }
    
    public function submit()
    {
        // Validate all meters that have values
        $rules = [
            'readingDate' => 'required|date|before_or_equal:today',
        ];
        
        foreach ($this->meters as $index => $meter) {
            if (!empty($meter['value'])) {
                $rules["meters.{$index}.value"] = "required|integer|min:0";
            }
        }
        
        $this->validate($rules);
        
        // Save readings for each meter that has a value
        $readingsSubmitted = 0;
        
        foreach ($this->meters as $meter) {
            // Skip meters without values
            if (empty($meter['value'])) {
                continue;
            }
            
            // Create the reading
            $reading = new Reading();
            $reading->water_meter_id = $meter['id'];
            $reading->user_id = Auth::id();
            $reading->reading_date = Carbon::parse($this->readingDate);
            $reading->value = $meter['value'];
            
            // Handle photo - move from temp to permanent storage
            if (!empty($meter['photo_path'])) {
                $tempPath = $meter['photo_path'];
                $photoPath = str_replace('readings-bulk-temp', 'reading-photos', $tempPath);
                
                // Ensure we keep the apartment folder structure
                // The path will already include the apartment ID from the temp folder
                
                // Move the file to permanent storage
                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->copy($tempPath, $photoPath);
                    $reading->photo_path = $photoPath;
                }
            }
            
            // Add note about AI extraction
            $notes = "Подадено чрез автоматично разпознаване на показания.";
            $reading->notes = $notes;
            
            $reading->save();
            $readingsSubmitted++;
        }
        
        // Clean up temporary files
        Storage::disk('public')->deleteDirectory('readings-bulk-temp');
        
        if ($readingsSubmitted > 0) {
            session()->flash('success', "{$readingsSubmitted} ".__('показания подадени успешно.'));
        } else {
            session()->flash('error', __('Не бяха подадени показания. Моля, въведете поне едно показание.'));
            return;
        }
        
        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.resident.bulk-readings-upload');
    }
}