<?php

namespace App\Livewire\Resident;

use App\Models\Apartment;
use App\Models\Reading;
use App\Models\WaterMeter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public $selectedPeriod = 'last_6_months';
    public $waterTypes = ['hot', 'cold'];
    
    public function mount()
    {
        // Initialize with default values
    }
    
    public function updatedSelectedPeriod()
    {
        // Update the chart data when the period changes
        $this->chartData = $this->getConsumptionChartData();
        $this->waterLossData = $this->getWaterLossData();
    }
    
    public function updatedSelectedApartmentId()
    {
        // Update the chart data when the apartment selection changes
        $this->chartData = $this->getConsumptionChartData();
    }
    
    public $chartData = [];
    public $waterLossData = [];
    public $selectedApartmentId = null;
    
    public function getConsumptionChartData()
    {
        $user = Auth::user();
        $apartmentIds = $user->apartments->pluck('id');
        
        // Filter by selected apartment if one is selected
        if ($this->selectedApartmentId) {
            $apartmentIds = [$this->selectedApartmentId];
        }
        
        $waterMeterIds = WaterMeter::whereIn('apartment_id', $apartmentIds)->pluck('id');
        
        // Determine date range based on selected period
        $startDate = null;
        switch ($this->selectedPeriod) {
            case 'last_3_months':
                $startDate = now()->subMonths(3)->startOfMonth();
                break;
            case 'last_6_months':
                $startDate = now()->subMonths(6)->startOfMonth();
                break;
            case 'last_12_months':
                $startDate = now()->subMonths(12)->startOfMonth();
                break;
        }
        
        // Get readings within date range
        $query = Reading::whereIn('water_meter_id', $waterMeterIds)
            ->where('reading_date', '>=', $startDate)
            ->join('water_meters', 'readings.water_meter_id', '=', 'water_meters.id')
            ->select(
                DB::raw('EXTRACT(YEAR FROM readings.reading_date) as year'),
                DB::raw('EXTRACT(MONTH FROM readings.reading_date) as month'),
                'water_meters.type',
                DB::raw('SUM(readings.consumption) as total_consumption')
            )
            ->groupBy('year', 'month', 'water_meters.type')
            ->orderBy('year')
            ->orderBy('month');
        
        $results = $query->get();
        
        // Generate all months in the period 
        $months = [];
        $currentDate = clone $startDate;
        $endDate = now();
        
        while ($currentDate->lte($endDate)) {
            $months[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $currentDate->format('M Y'),
                'year' => $currentDate->year,
                'month' => $currentDate->month,
                'hot' => 0,
                'cold' => 0
            ];
            $currentDate->addMonth();
        }
        
        // Fill in actual data where available
        foreach ($results as $result) {
            $year = (int)$result->year;
            $month = (int)$result->month;
            
            // Find the matching month
            foreach ($months as &$monthData) {
                if ($monthData['year'] == $year && $monthData['month'] == $month) {
                    if ($result->type === 'hot') {
                        $monthData['hot'] = (float)$result->total_consumption;
                    } else {
                        $monthData['cold'] = (float)$result->total_consumption;
                    }
                    break;
                }
            }
        }
        
        // Format data for FluxUI chart
        $chartData = [];
        
        foreach ($months as $month) {
            $chartData[] = [
                'date' => $month['label'],
                'hot' => $month['hot'],
                'cold' => $month['cold']
            ];
        }
        
        return $chartData;
    }
    
    public function getWaterLossData()
    {
        // Determine date range based on selected period
        $startDate = null;
        switch ($this->selectedPeriod) {
            case 'last_3_months':
                $startDate = now()->subMonths(3)->startOfMonth();
                break;
            case 'last_6_months':
                $startDate = now()->subMonths(6)->startOfMonth();
                break;
            case 'last_12_months':
                $startDate = now()->subMonths(12)->startOfMonth();
                break;
        }
        
        // Generate all months in the period 
        $months = [];
        $currentDate = clone $startDate;
        $endDate = now();
        
        while ($currentDate->lte($endDate)) {
            $months[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $currentDate->format('M Y'),
                'year' => $currentDate->year,
                'month' => $currentDate->month,
                'cold_water_loss' => 0,
                'hot_water_loss' => 0
            ];
            $currentDate->addMonth();
        }
        
        // Get central cold water meter readings
        $centralColdMeters = WaterMeter::where('type', 'central-cold')->pluck('id');
        
        $centralColdReadings = Reading::whereIn('water_meter_id', $centralColdMeters)
            ->where('reading_date', '>=', $startDate)
            ->select(
                DB::raw('EXTRACT(YEAR FROM readings.reading_date) as year'),
                DB::raw('EXTRACT(MONTH FROM readings.reading_date) as month'),
                DB::raw('SUM(readings.consumption) as total_consumption')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Get central hot water meter readings
        $centralHotMeters = WaterMeter::where('type', 'central-hot')->pluck('id');
        
        $centralHotReadings = Reading::whereIn('water_meter_id', $centralHotMeters)
            ->where('reading_date', '>=', $startDate)
            ->select(
                DB::raw('EXTRACT(YEAR FROM readings.reading_date) as year'),
                DB::raw('EXTRACT(MONTH FROM readings.reading_date) as month'),
                DB::raw('SUM(readings.consumption) as total_consumption')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Get apartment cold water meter readings
        $apartmentColdMeters = WaterMeter::where('type', 'cold')
            ->whereNotNull('apartment_id')
            ->pluck('id');
        
        $apartmentColdReadings = Reading::whereIn('water_meter_id', $apartmentColdMeters)
            ->where('reading_date', '>=', $startDate)
            ->select(
                DB::raw('EXTRACT(YEAR FROM readings.reading_date) as year'),
                DB::raw('EXTRACT(MONTH FROM readings.reading_date) as month'),
                DB::raw('SUM(readings.consumption) as total_consumption')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Get apartment hot water meter readings
        $apartmentHotMeters = WaterMeter::where('type', 'hot')
            ->whereNotNull('apartment_id')
            ->pluck('id');
        
        $apartmentHotReadings = Reading::whereIn('water_meter_id', $apartmentHotMeters)
            ->where('reading_date', '>=', $startDate)
            ->select(
                DB::raw('EXTRACT(YEAR FROM readings.reading_date) as year'),
                DB::raw('EXTRACT(MONTH FROM readings.reading_date) as month'),
                DB::raw('SUM(readings.consumption) as total_consumption')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Calculate water loss for each month
        foreach ($months as &$monthData) {
            $year = $monthData['year'];
            $month = $monthData['month'];
            
            // Get central cold meter readings for this month
            $centralColdReading = $centralColdReadings->first(function ($reading) use ($year, $month) {
                return (int)$reading->year === $year && 
                       (int)$reading->month === $month;
            });
            
            // Get apartment cold meter readings for this month
            $apartmentColdReading = $apartmentColdReadings->first(function ($reading) use ($year, $month) {
                return (int)$reading->year === $year && 
                       (int)$reading->month === $month;
            });
            
            // Calculate cold water loss
            $centralColdValue = $centralColdReading ? (float)$centralColdReading->total_consumption : 0;
            $apartmentColdValue = $apartmentColdReading ? (float)$apartmentColdReading->total_consumption : 0;
            
            $monthData['cold_water_loss'] = max(0, $centralColdValue - $apartmentColdValue);
            
            // Get central hot meter readings for this month
            $centralHotReading = $centralHotReadings->first(function ($reading) use ($year, $month) {
                return (int)$reading->year === $year && 
                       (int)$reading->month === $month;
            });
            
            // Get apartment hot meter readings for this month
            $apartmentHotReading = $apartmentHotReadings->first(function ($reading) use ($year, $month) {
                return (int)$reading->year === $year && 
                       (int)$reading->month === $month;
            });
            
            // Calculate hot water loss
            $centralHotValue = $centralHotReading ? (float)$centralHotReading->total_consumption : 0;
            $apartmentHotValue = $apartmentHotReading ? (float)$apartmentHotReading->total_consumption : 0;
            
            $monthData['hot_water_loss'] = max(0, $centralHotValue - $apartmentHotValue);
        }
        
        // Format data for FluxUI chart
        $chartData = [];
        
        foreach ($months as $month) {
            $chartData[] = [
                'date' => $month['label'],
                'cold_water_loss' => $month['cold_water_loss'],
                'hot_water_loss' => $month['hot_water_loss']
            ];
        }
        
        return $chartData;
    }
    
    public function getMaxConsumptionValue()
    {
        $chartData = $this->getConsumptionChartData();
        $allValues = collect($chartData)->flatMap(function ($item) {
            return [$item['hot'], $item['cold']];
        });
            
        return $allValues->max(); // Add 20% padding to the max value
    }
    
    public function getMaxWaterLossValue()
    {
        $waterLossData = $this->getWaterLossData();
        $allValues = collect($waterLossData)->flatMap(function ($item) {
            return [$item['cold_water_loss'], $item['hot_water_loss']];
        });
            
        return $allValues->max(); // Return the maximum value
    }
    
    public function getApartmentReadingsTable()
    {
        // Determine date range based on selected period
        $startDate = null;
        switch ($this->selectedPeriod) {
            case 'last_3_months':
                $startDate = now()->subMonths(3)->startOfMonth();
                break;
            case 'last_6_months':
                $startDate = now()->subMonths(6)->startOfMonth();
                break;
            case 'last_12_months':
                $startDate = now()->subMonths(12)->startOfMonth();
                break;
        }
        
        // Generate all months in the period
        $months = [];
        $currentDate = clone $startDate;
        $endDate = now()->endOfMonth();
        
        while ($currentDate->lte($endDate)) {
            $months[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $currentDate->format('M Y'),
                'year' => $currentDate->year,
                'month' => $currentDate->month,
            ];
            $currentDate->addMonth();
        }
        
        // Get all apartments
        $apartments = Apartment::orderBy('floor')->orderBy('number')->get();
        
        // Get all water meters
        $waterMeters = WaterMeter::whereNotNull('apartment_id')
            ->with('apartment')
            ->get()
            ->groupBy('apartment_id');
        
        // Get all readings within the date range
        $readings = Reading::whereIn('water_meter_id', WaterMeter::whereNotNull('apartment_id')->pluck('id'))
            ->where('reading_date', '>=', $startDate)
            ->with('waterMeter')
            ->get();
        
        // Prepare the result data structure
        $tableData = [
            'months' => $months,
            'apartments' => [],
        ];
        
        // Process each apartment
        foreach ($apartments as $apartment) {
            $apartmentData = [
                'id' => $apartment->id,
                'floor' => $apartment->floor,
                'number' => $apartment->number,
                'name' => "Етаж {$apartment->floor}, Ап. {$apartment->number}",
                'readings' => [],
            ];
            
            // Get the water meters for this apartment
            $apartmentMeters = $waterMeters[$apartment->id] ?? collect();
            $meterIds = $apartmentMeters->pluck('id')->toArray();
            
            // Process each month
            foreach ($months as $month) {
                $year = $month['year'];
                $monthNum = $month['month'];
                
                // Check if there are readings for this month for any of the apartment's meters
                $monthReadings = $readings->filter(function ($reading) use ($meterIds, $year, $monthNum) {
                    return in_array($reading->water_meter_id, $meterIds) && 
                           Carbon::parse($reading->reading_date)->month == $monthNum && 
                           Carbon::parse($reading->reading_date)->year == $year;
                });
                
                // Count total meters and submitted readings
                $totalMeters = $apartmentMeters->count();
                $submittedReadings = 0;
                $uniqueMetersWithReadings = collect();
                
                foreach ($monthReadings as $reading) {
                    // Count unique meters with readings
                    if (!$uniqueMetersWithReadings->contains($reading->water_meter_id)) {
                        $uniqueMetersWithReadings->push($reading->water_meter_id);
                        $submittedReadings++;
                    }
                }
                
                // Calculate status
                $status = 'none'; // Default: No readings
                
                if ($submittedReadings > 0) {
                    if ($submittedReadings == $totalMeters) {
                        $status = 'complete'; // All readings submitted
                    } else {
                        $status = 'partial'; // Some readings submitted
                    }
                }
                
                // Store the month data
                $apartmentData['readings'][] = [
                    'year' => $year,
                    'month' => $monthNum,
                    'status' => $status,
                    'submitted' => $submittedReadings,
                    'total' => $totalMeters,
                ];
            }
            
            $tableData['apartments'][] = $apartmentData;
        }
        
        return $tableData;
    }
    
    public function render()
    {
        $user = Auth::user();
        
        // Get apartments associated with this user
        $apartments = $user->apartments;
        
        // Initialize selectedApartmentId if not set and user has apartments
        if (!$this->selectedApartmentId && $apartments->count() > 0) {
            $this->selectedApartmentId = $apartments->first()->id;
        }
        
        // Get all water meters for these apartments
        $waterMeters = WaterMeter::whereIn('apartment_id', $apartments->pluck('id'))
            ->with(['apartment', 'latestReading'])
            ->get();
            
        // Group water meters by apartment
        $metersByApartment = $waterMeters->groupBy('apartment_id');
        
        // Get chart data
        $this->chartData = $this->getConsumptionChartData();
        $maxValue = $this->getMaxConsumptionValue();
        
        // Get water loss data
        $this->waterLossData = $this->getWaterLossData();
        $maxLossValue = $this->getMaxWaterLossValue();
        
        // Get the readings status table data
        $readingsTableData = $this->getApartmentReadingsTable();
        
        return view('livewire.resident.dashboard', [
            'apartments' => $apartments,
            'metersByApartment' => $metersByApartment,
            'maxValue' => $maxValue,
            'maxLossValue' => $maxLossValue,
            'readingsTableData' => $readingsTableData
        ]);
    }
}
