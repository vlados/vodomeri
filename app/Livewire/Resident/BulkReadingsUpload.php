<?php

namespace App\Livewire\Resident;
ini_set("max_execution_time", 600);
use App\Models\Reading;
use App\Models\WaterMeter;
use App\Services\OpenAIService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
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
    public $foreignMeters = [];
    public $processingComplete = false;
    public $recognizedMeters = [];

    public function mount()
    {
        // Set the reading date to the last day of the previous month by default
        $this->readingDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
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

    /**
     * Remove a photo from the array
     */
    public function removePhoto($index)
    {
        if (isset($this->photos[$index])) {
            // Create a new array without the removed photo
            $photos = $this->photos;
            unset($photos[$index]);
            $this->photos = array_values($photos); // Reindex array
        }
    }

    public function processPhotos()
    {
        $this->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:10240', // Increased max size to 10MB
            'readingDate' => 'required|date|before_or_equal:today',
        ]);

        $this->isProcessing = true;
        $this->extractionResults = [];
        $this->recognizedMeters = [];

        try {
            // Check OpenAI configuration
            if (!OpenAIService::isConfigured()) {
                throw new \Exception('API за разпознаване на показания не е конфигуриран. Моля, обърнете се към администратор.');
            }

            // Store photos temporarily
            $photoPaths = [];
            foreach ($this->photos as $photo) {
                // Store in apartment-specific folder
                $apartmentId = $this->selectedApartmentId;
                $path = $photo->store("readings-bulk-temp/apartment-{$apartmentId}", 'public');
                $photoPaths[] = $path;
            }

            // Store valid apartment serial numbers for verification
            $validSerialNumbers = [];

            // Convert meters array to format expected by OpenAI service
            $metersForAI = [];
            foreach ($this->meters as $index => $meter) {
                $metersForAI[] = [
                    'id' => $meter['id'],
                    'serial_number' => $meter['serial_number'],
                    'type' => $meter['type'],
                    'location' => $meter['location'],
                ];

                // Store valid serial numbers for this apartment
                $validSerialNumbers[$meter['serial_number']] = $meter['id'];
            }

            // Extract readings from all photos
            $openAIService = new OpenAIService();
            $this->extractionResults = $openAIService->extractMultipleReadings($photoPaths, $metersForAI);

            // Store any found meters not belonging to this apartment
            $foreignMeters = [];

            if ($this->extractionResults['success']) {
                // Process results
                foreach ($this->extractionResults['results'] as $result) {
                    $meterId = $result['meter_id'];
                    $serialNumber = $result['serial_number'];

                    // Verify this meter belongs to the current apartment
                    if (!in_array($meterId, array_values($validSerialNumbers))) {
                        // Log the detection of a foreign meter
                        \Log::warning('Foreign water meter detected in photo', [
                            'extracted_serial' => $serialNumber,
                            'meter_id' => $meterId,
                            'apartment_id' => $this->selectedApartmentId,
                            'user_id' => auth()->id(),
                            'valid_meters' => array_values($validSerialNumbers)
                        ]);

                        // Add to foreign meters list
                        $foreignMeters[] = [
                            'serial_number' => $serialNumber,
                            'reading' => $result['extracted_reading'],
                            'image_path' => $result['image_path']
                        ];

                        // Skip processing this meter
                        continue;
                    }

                    // Find the meter in our array
                    foreach ($this->meters as $index => $meter) {
                        if ($meter['id'] == $meterId) {
                            // Update the meter with the extracted value
                            // Process the reading with decimals
                            $reading = $result['extracted_reading'];
                            // Clean the reading, keeping digits and decimal point
                            $reading = preg_replace('/[^0-9.]/', '', $reading);

                            // Ensure we have a single decimal point
                            if (substr_count($reading, '.') > 1) {
                                // Multiple decimal points - keep only the first one
                                $parts = explode('.', $reading);
                                $reading = $parts[0] . '.' . $parts[1];
                            }

                            // Format to 3 decimal places
                            $reading = number_format((float)$reading, 3, '.', '');

                            // Validate the reading value - must be greater than previous reading
                            $previousValue = $meter['previous_value'];
                            if ((float)$reading < (float)$previousValue) {
                                // If extracted reading is less than previous, flag it but still set the value
                                $this->meters[$index]['value_warning'] = "Извлеченото показание ({$reading}) е по-малко от предишното ({$previousValue}). Моля, проверете стойността.";
                            }

                            $this->meters[$index]['value'] = $reading;
                            $this->meters[$index]['photo_path'] = $result['image_path'];
                            $this->meters[$index]['confidence'] = $result['confidence'] ?? 'low';
                            $this->meters[$index]['match_confidence'] = $result['match_confidence'] ?? 0;
                            $this->meters[$index]['is_recognized'] = true;
                            $this->recognizedMeters[] = $index;
                            break;
                        }
                    }
                }

                // Provide helpful messages based on detection results
                if (!empty($foreignMeters)) {
                    // Store foreign meters data in a property for display
                    $this->foreignMeters = $foreignMeters;

                    session()->flash('error', 'Открихме ' . count($foreignMeters) . ' водомер(а), които не принадлежат на този апартамент. Снимките трябва да съдържат само водомери от избрания апартамент.');
                }

                if (empty($this->recognizedMeters)) {
                    session()->flash('warning', 'Не успяхме да разпознаем водомери от този апартамент на снимките. Опитайте с по-ясни снимки или въведете показанията ръчно.');
                } elseif (empty($foreignMeters)) {
                    // Only show success if no foreign meters detected
                    session()->flash('success', 'Успешно разпознахме ' . count($this->recognizedMeters) . ' водомера. Моля, проверете показанията преди да ги подадете.');
                }
            } else {
                // Log failed detection
                \Log::warning('OpenAI failed to detect meters', [
                    'user_id' => auth()->id(),
                    'apartment_id' => $this->selectedApartmentId,
                    'date' => $this->readingDate,
                    'image_count' => count($photoPaths),
                    'meter_count' => count($metersForAI),
                    'error_message' => $this->extractionResults['message'] ?? 'Unknown error',
                    'raw_response' => $this->extractionResults
                ]);

                session()->flash('warning', $this->extractionResults['message'] ?? 'Неуспешно разпознаване на водомери. Опитайте с по-ясни снимки.');
            }

        } catch (\Exception $e) {
            // Log detailed exception information
            \Log::error('BulkReadingsUpload error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'apartment_id' => $this->selectedApartmentId,
                'date' => $this->readingDate,
                'image_count' => count($photoPaths ?? []),
                'exception_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Грешка при обработката на снимките: ' . $e->getMessage());
        }

        $this->isProcessing = false;
        $this->processingComplete = true;
    }

    /**
     * Update a single reading value
     */
    public function updateReading($index, $value)
    {
        $this->meters[$index]['value'] = $value;
    }

    /**
     * Manually add a meter that wasn't automatically detected
     */
    public function addMeterManually($index)
    {
        if (isset($this->meters[$index]) && !$this->meters[$index]['is_recognized']) {
            // Mark meter as manually added
            $this->meters[$index]['is_recognized'] = true;
            $this->meters[$index]['confidence'] = 'low';
            $this->meters[$index]['is_manually_added'] = true;

            // Add to recognized meters array
            $this->recognizedMeters[] = $index;
        }
    }

    public function resetForm()
    {
        $this->photos = [];
        $this->extractionResults = [];
        $this->recognizedMeters = [];
        $this->foreignMeters = [];
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
            if ($meter['is_recognized'] && !empty($meter['value'])) {
                // Ensure value is greater than or equal to previous value
                $previousValue = (float) $meter['previous_value'];
                $rules["meters.{$index}.value"] = "required|numeric|min:{$previousValue}";
            }
        }

        $this->validate($rules);

        // Save readings for each meter that has a value
        $readingsSubmitted = 0;
        $totalConsumption = 0;

        foreach ($this->meters as $meter) {
            // Skip meters without values or not recognized
            if (!$meter['is_recognized'] || empty($meter['value'])) {
                continue;
            }

            // Calculate consumption for tracking
            $previousValue = (float) $meter['previous_value'];
            $newValue = (float) $meter['value'];
            $consumption = $newValue - $previousValue;
            $totalConsumption += $consumption;

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

                // Create directory for proper storage if it doesn't exist
                $directory = pathinfo($photoPath, PATHINFO_DIRNAME);
                Storage::disk('public')->makeDirectory($directory);

                // Move the file to permanent storage
                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->copy($tempPath, $photoPath);
                    $reading->photo_path = $photoPath;
                }
            }

            // Build detailed notes about the reading
            $notes = "Показание добавено чрез AI разпознаване на снимка.";

            // Add info about the recognition if available
            if (isset($meter['confidence'])) {
                $confidenceMap = [
                    'high' => 'висока',
                    'medium' => 'средна',
                    'low' => 'ниска',
                ];
                $confidence = $confidenceMap[$meter['confidence']] ?? $meter['confidence'];
                $notes .= " Сигурност: {$confidence}.";
            }

            // Add info about manual editing if applicable
            if (isset($meter['is_manually_added']) && $meter['is_manually_added']) {
                $notes .= " Водомерът е добавен ръчно.";
            }

            // Add consumption info
            $notes .= " Консумация: " . number_format($consumption, 3) . " м³.";

            // Store notes
            $reading->notes = $notes;

            // Save the reading
            $reading->save();
            $readingsSubmitted++;
        }

        // Clean up temporary files
        Storage::disk('public')->deleteDirectory('readings-bulk-temp');

        if ($readingsSubmitted > 0) {
            $totalConsumptionFormatted = number_format($totalConsumption, 3);
            session()->flash('success', "Успешно подадени {$readingsSubmitted} показания с обща консумация {$totalConsumptionFormatted} м³.");
        } else {
            session()->flash('error', 'Не бяха подадени показания. Моля, въведете поне едно показание.');
            return;
        }

        return redirect()->route('dashboard');
    }

    public function render()
    {
        $title = 'Групово качване на показания';
        View::share("title", $title);
        return view('livewire.resident.bulk-readings-upload', [
            'title' => $title
        ]);
    }
}
