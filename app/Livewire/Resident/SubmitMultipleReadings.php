<?php

namespace App\Livewire\Resident;

use App\Models\Reading;
use App\Models\WaterMeter;
use App\Services\OpenAIService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;

class SubmitMultipleReadings extends Component
{
    use WithFileUploads;
    
    public $readingDate;
    public $meters = [];
    public $selectedApartmentId = null;
    public array|Collection $apartments = [];
    public $verificationResults = [];
    public $aiVerificationEnabled = true;
    
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
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function rules(): array
    {
        $rules = [
            'readingDate' => 'required|date|before_or_equal:today',
        ];
        
        // Require readings for all meters
        foreach ($this->meters as $index => $meter) {
            // All meters must have a reading value
            $rules["meters.{$index}.value"] = "required|numeric|min:{$meter['previous_value']}";
            
            // Determine if this is the first reading (no previous reading)
            $isFirstReading = $meter['previous_date'] === 'Initial';
            
            // Photo is required for first readings, optional for subsequent readings
            if ($isFirstReading) {
                $rules["meters.{$index}.photo"] = "required|image|max:5120";
            } else {
                $rules["meters.{$index}.photo"] = "nullable|image|max:5120";
            }
        }
        
        return $rules;
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        $messages = [
            'readingDate.required' => 'Моля, въведете дата на отчитане.',
            'readingDate.date' => 'Датата на отчитане трябва да бъде валидна дата.',
            'readingDate.before_or_equal' => 'Датата на отчитане не може да бъде в бъдещето.',
        ];
        
        foreach ($this->meters as $index => $meter) {
            $meterType = $meter['type'] === 'hot' ? 'Топла' : 'Студена';
            $location = $meter['location'] ? " ({$meter['location']})" : '';
            
            $messages["meters.{$index}.value.required"] = "Моля, въведете показание.";
            $messages["meters.{$index}.value.numeric"] = "Показанието трябва да бъде число.";
            $messages["meters.{$index}.value.min"] = "Показанието трябва да бъде по-голямо или равно на предишното показание ({$meter['previous_value']} m³).";
            
            $messages["meters.{$index}.photo.required"] = "Снимката е задължителна за първо отчитане на водомера.";
            $messages["meters.{$index}.photo.image"] = "Снимката трябва да бъде изображение.";
            $messages["meters.{$index}.photo.max"] = "Снимката не може да бъде по-голяма от 5MB.";
        }
        
        return $messages;
    }
    
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = [
            'readingDate' => 'дата на отчитане',
        ];
        
        foreach ($this->meters as $index => $meter) {
            $meterType = $meter['type'] === 'hot' ? 'Топла' : 'Студена';
            $location = $meter['location'] ? " ({$meter['location']})" : '';
            $attributes["meters.{$index}.value"] = "показание на {$meterType} вода{$location}";
            $attributes["meters.{$index}.photo"] = "снимка на {$meterType} вода{$location}";
        }
        
        return $attributes;
    }
    
    /**
     * Verify meter readings using OpenAI Vision API
     */
    public function verifyReadings()
    {
        $this->validate();
        
        // Reset verification results
        $this->verificationResults = [];
        
        // Skip verification if disabled
        if (!$this->aiVerificationEnabled) {
            $this->submit();
            return;
        }
        
        $openAiService = new OpenAIService();
        $needManualVerification = false;
        
        foreach ($this->meters as $index => $meterData) {
            // Skip meters without photos
            if (empty($meterData['photo'])) {
                $this->verificationResults[$index] = [
                    'status' => 'skipped',
                    'message' => 'Няма снимка за верификация'
                ];
                continue;
            }
            
            // Store the photo temporarily
            $photoPath = $meterData['photo']->store('reading-photos-temp', 'public');
            
            // Analyze the meter reading with OpenAI
            $analysis = $openAiService->analyzeMeterReading(
                $photoPath, 
                $meterData['serial_number'],
                (float) $meterData['value']
            );
            
            // Store results
            $this->verificationResults[$index] = [
                'status' => $analysis['success'] ? 'success' : 'error',
                'message' => $analysis['message'],
                'details' => $analysis,
                'photo_path' => $photoPath
            ];
            
            // Flag if any meter needs manual verification
            if (!$analysis['success']) {
                $needManualVerification = true;
            }
        }
        
        // If all readings are verified or there's nothing to verify, proceed with submission
        if (!$needManualVerification) {
            $this->submit();
        }
    }
    
    /**
     * Submit readings after verification (or skip verification)
     */
    public function submit()
    {
        $this->validate();
        
        // Save readings for each meter
        $readingsSubmitted = 0;
        
        foreach ($this->meters as $index => $meterData) {
            
            // Handle photo upload if provided
            $photoPath = null;
            
            if (!empty($meterData['photo'])) {
                // If we already verified and have a temporary photo path, move it to permanent storage
                if (isset($this->verificationResults[$index]['photo_path'])) {
                    $tempPath = $this->verificationResults[$index]['photo_path'];
                    $photoPath = str_replace('reading-photos-temp', 'reading-photos', $tempPath);
                    
                    // Rename the file to permanent storage
                    if (\Storage::disk('public')->exists($tempPath)) {
                        \Storage::disk('public')->copy($tempPath, $photoPath);
                        \Storage::disk('public')->delete($tempPath);
                    } else {
                        // If temp file doesn't exist, store the original upload
                        $photoPath = $meterData['photo']->store('reading-photos', 'public');
                    }
                } else {
                    // No verification was done, store the original upload
                    $photoPath = $meterData['photo']->store('reading-photos', 'public');
                }
            }
            
            // Add verification results to notes if available
            $notes = $meterData['notes'] ?? '';
            
            if (isset($this->verificationResults[$index]) && $this->verificationResults[$index]['status'] !== 'skipped') {
                $verificationStatus = $this->verificationResults[$index]['status'] === 'success' ? 'успешна' : 'неуспешна';
                $notes .= ($notes ? "\n" : "") . "AI Верификация: {$verificationStatus}. " . 
                         $this->verificationResults[$index]['message'];
                
                // Add confidence level if available
                if (isset($this->verificationResults[$index]['details']['confidence'])) {
                    $confidenceMap = [
                        'high' => 'висока',
                        'medium' => 'средна',
                        'low' => 'ниска'
                    ];
                    $confidence = $confidenceMap[$this->verificationResults[$index]['details']['confidence']] ?? $this->verificationResults[$index]['details']['confidence'];
                    $notes .= " Сигурност: {$confidence}.";
                }
            }
            
            // Create the reading
            $reading = new Reading();
            $reading->water_meter_id = $meterData['id'];
            $reading->user_id = Auth::id();
            $reading->reading_date = Carbon::parse($this->readingDate);
            $reading->value = $meterData['value'];
            $reading->notes = $notes ?: null;
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
        
        // Clean up any leftover temporary files
        \Storage::disk('public')->deleteDirectory('reading-photos-temp');
        
        return redirect()->route('dashboard');
    }
    
    /**
     * Skip verification and submit readings directly
     */
    public function skipVerification()
    {
        $this->aiVerificationEnabled = false;
        $this->submit();
    }
    
    public function render()
    {
        return view('livewire.resident.submit-multiple-readings');
    }
}