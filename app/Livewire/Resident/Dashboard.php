<?php

namespace App\Livewire\Resident;

use App\Models\Apartment;
use App\Models\Reading;
use App\Models\WaterMeter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    
    public $chartData = [];
    public $waterLossData = [];
    
    public function getConsumptionChartData()
    {
        $user = Auth::user();
        $apartmentIds = $user->apartments->pluck('id');
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
                'water_loss' => 0
            ];
            $currentDate->addMonth();
        }
        
        // Get central meter readings (only cold water)
        $centralMeters = WaterMeter::where('type', 'central')->pluck('id');
        
        $centralReadings = Reading::whereIn('water_meter_id', $centralMeters)
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
        
        // Get apartment meter readings (only cold water)
        $apartmentMeters = WaterMeter::whereNotIn('id', $centralMeters)
            ->where('type', 'cold')
            ->pluck('id');
        
        $apartmentReadings = Reading::whereIn('water_meter_id', $apartmentMeters)
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
            
            // Get central meter readings for this month (cold water only)
            $centralReading = $centralReadings->first(function ($reading) use ($year, $month) {
                return (int)$reading->year === $year && 
                       (int)$reading->month === $month;
            });
            
            // Get apartment meter readings for this month (cold water only)
            $apartmentReading = $apartmentReadings->first(function ($reading) use ($year, $month) {
                return (int)$reading->year === $year && 
                       (int)$reading->month === $month;
            });
            
            // Calculate water loss (cold water only)
            $centralValue = $centralReading ? (float)$centralReading->total_consumption : 0;
            $apartmentValue = $apartmentReading ? (float)$apartmentReading->total_consumption : 0;
            
            $monthData['water_loss'] = max(0, $centralValue - $apartmentValue);
        }
        
        // Format data for FluxUI chart
        $chartData = [];
        
        foreach ($months as $month) {
            $chartData[] = [
                'date' => $month['label'],
                'water_loss' => $month['water_loss']
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
            
        return $allValues->max() * 1.2; // Add 20% padding to the max value
    }
    
    public function getMaxWaterLossValue()
    {
        $waterLossData = $this->getWaterLossData();
        $allValues = collect($waterLossData)->pluck('water_loss');
            
        return $allValues->max() * 1.2; // Add 20% padding to the max value
    }
    
    public function render()
    {
        $user = Auth::user();
        
        // Get apartments associated with this user
        $apartments = $user->apartments;
        
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
        
        return view('livewire.resident.dashboard', [
            'apartments' => $apartments,
            'metersByApartment' => $metersByApartment,
            'maxValue' => $maxValue,
            'maxLossValue' => $maxLossValue
        ]);
    }
}
