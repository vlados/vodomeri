<?php

namespace App\Livewire\Resident;

use App\Models\WaterMeter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Livewire\Component;

class EditWaterMeter extends Component
{
    public $waterMeter;

    public $meterId;

    public $serialNumber = '';

    public $type = '';

    public $location = '';

    public $installationDate;

    public $initialReading = 0;

    public function mount($meterId)
    {
        $this->meterId = $meterId;
        $user = Auth::user();

        // Get the water meter and check if user has access to its apartment
        $this->waterMeter = WaterMeter::findOrFail($meterId);

        // Check if the user has access to the apartment this meter belongs to
        $userApartmentIds = $user->apartments->pluck('id')->toArray();
        if (! in_array($this->waterMeter->apartment_id, $userApartmentIds)) {
            session()->flash('error', 'Нямате достъп до този водомер.');

            return redirect()->route('dashboard');
        }

        // Fill the form with current values
        $this->serialNumber = $this->waterMeter->serial_number;
        $this->type = $this->waterMeter->type;
        $this->location = $this->waterMeter->location;
        $this->installationDate = $this->waterMeter?->installation_date?->format('Y-m-d');
        $this->initialReading = $this->waterMeter->initial_reading;
    }

    public function save()
    {
        $this->validate([
            'serialNumber' => 'required|string|max:50|unique:water_meters,serial_number,'.$this->meterId,
            'type' => 'required|in:hot,cold',
            'location' => 'nullable|string|max:255',
            'installationDate' => 'required|date|before_or_equal:today',
            'initialReading' => 'required|numeric|min:0',
        ]);

        // Update the water meter
        $this->waterMeter->serial_number = $this->serialNumber;
        $this->waterMeter->type = $this->type;
        $this->waterMeter->location = $this->location;
        $this->waterMeter->installation_date = $this->installationDate;
        $this->waterMeter->initial_reading = $this->initialReading;
        $this->waterMeter->save();

        session()->flash('success', __('Водомерът е обновен успешно.'));

        return redirect()->route('dashboard');
    }

    public function render()
    {
        $title = 'Редактиране на водомер';
        View::share('title', $title);

        return view('livewire.resident.edit-water-meter', [
            'title' => $title,
        ]);
    }
}
