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
    public $sortBy = 'floor';
    public $sortDirection = 'asc';

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

    public function updatedSelectedStatsMonth()
    {
        // No need to reload data, just refresh the view with the selected month
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

        // Get unique month/year combinations from results
        $uniqueMonths = collect();
        foreach ($results as $result) {
            $key = $result->year . '-' . $result->month;
            if (!$uniqueMonths->contains('key', $key)) {
                $uniqueMonths->push([
                    'key' => $key,
                    'year' => (int)$result->year,
                    'month' => (int)$result->month,
                    'date' => Carbon::createFromDate($result->year, $result->month, 1)->format('Y-m-d'),
                    'label' => Carbon::createFromDate($result->year, $result->month, 1)->format('M Y'),
                    'hot' => 0,
                    'cold' => 0
                ]);
            }
        }

        // Sort months chronologically
        $months = $uniqueMonths->sortBy(function ($month) {
            return $month['year'] * 100 + $month['month']; // Sort by year and month
        })->values()->all();

        // Fill in actual data where available
        foreach ($results as $result) {
            $year = (int) $result->year;
            $month = (int) $result->month;

            // Find the matching month
            foreach ($months as &$monthData) {
                if ($monthData['year'] == $year && $monthData['month'] == $month) {
                    if ($result->type === 'hot') {
                        $monthData['hot'] = (float) $result->total_consumption;
                    } elseif ($result->type === 'cold') {
                        $monthData['cold'] = (float) $result->total_consumption;
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
                'cold' => $month['cold'],
            ];
        }

        return $chartData;
    }

    public $selectedStatsMonth = 0;

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

        // Combine all readings to identify unique months with data
        $allReadings = collect()
            ->merge($centralColdReadings)
            ->merge($centralHotReadings)
            ->merge($apartmentColdReadings)
            ->merge($apartmentHotReadings);

        // Extract unique year-month combinations
        $uniqueMonths = collect();
        foreach ($allReadings as $reading) {
            $key = $reading->year . '-' . $reading->month;
            if (!$uniqueMonths->contains('key', $key)) {
                $uniqueMonths->push([
                    'key' => $key,
                    'year' => (int)$reading->year,
                    'month' => (int)$reading->month,
                    'date' => Carbon::createFromDate($reading->year, $reading->month, 1)->format('Y-m-d'),
                    'label' => Carbon::createFromDate($reading->year, $reading->month, 1)->format('M Y'),
                    'cold_water_loss' => 0,
                    'hot_water_loss' => 0,
                    'total_water_loss' => 0,
                    'cold_water_total' => 0,
                    'hot_water_total' => 0,
                    'apartment_cold_consumption' => 0,
                    'apartment_hot_consumption' => 0,
                    'apartment_total_consumption' => 0,
                    'apartment_count' => 0,
                ]);
            }
        }

        // Sort months chronologically
        $months = $uniqueMonths->sortBy(function ($month) {
            return $month['year'] * 100 + $month['month']; // Sort by year and month
        })->values()->all();

        // Calculate water loss for each month
        foreach ($months as &$monthData) {
            $year = $monthData['year'];
            $month = $monthData['month'];

            // Get central cold meter readings for this month
            $centralColdReading = $centralColdReadings->first(function ($reading) use ($year, $month) {
                return (int) $reading->year === $year &&
                       (int) $reading->month === $month;
            });

            // Get apartment cold meter readings for this month
            $apartmentColdReading = $apartmentColdReadings->first(function ($reading) use ($year, $month) {
                return (int) $reading->year === $year &&
                       (int) $reading->month === $month;
            });

            // Calculate cold water values
            $centralColdValue = $centralColdReading ? (float) $centralColdReading->total_consumption : 0;
            $apartmentColdValue = $apartmentColdReading ? (float) $apartmentColdReading->total_consumption : 0;

            $monthData['cold_water_total'] = $centralColdValue;
            $monthData['apartment_cold_consumption'] = $apartmentColdValue;

            // Get central hot meter readings for this month
            $centralHotReading = $centralHotReadings->first(function ($reading) use ($year, $month) {
                return (int) $reading->year === $year &&
                       (int) $reading->month === $month;
            });

            // Get apartment hot meter readings for this month
            $apartmentHotReading = $apartmentHotReadings->first(function ($reading) use ($year, $month) {
                return (int) $reading->year === $year &&
                       (int) $reading->month === $month;
            });

            // Calculate hot water values
            $centralHotValue = $centralHotReading ? (float) $centralHotReading->total_consumption : 0;
            $apartmentHotValue = $apartmentHotReading ? (float) $apartmentHotReading->total_consumption : 0;

            $monthData['hot_water_total'] = $centralHotValue;
            $monthData['apartment_hot_consumption'] = $apartmentHotValue;

            // Calculate total apartment consumption
            $monthData['apartment_total_consumption'] = $apartmentColdValue + $apartmentHotValue;

            // Calculate loss values according to the requested formula:
            // Total water loss = Central cold water - Sum of all water meters (cold & hot)
            $monthData['cold_water_loss'] = max(0, $centralColdValue - ($apartmentColdValue + $apartmentHotValue));

            // Hot water loss = Central hot water - Sum of hot water meters
            $monthData['hot_water_loss'] = max(0, $centralHotValue - $apartmentHotValue);

            // Total water loss is the sum of cold and hot water losses
            $monthData['total_water_loss'] = $monthData['cold_water_loss'] + $monthData['hot_water_loss'];

            // Count apartments with readings for this month
            $monthData['apartment_count'] = Apartment::whereHas('waterMeters.readings', function ($query) use ($year, $month) {
                $query->whereYear('reading_date', $year)
                    ->whereMonth('reading_date', $month);
            })->count();
        }

        // Format data for FluxUI chart
        $chartData = [];

        foreach ($months as $month) {
            $chartData[] = [
                'date' => $month['label'],
                'cold_water_loss' => $month['cold_water_loss'],
                'hot_water_loss' => $month['hot_water_loss'],
                'total_water_loss' => $month['total_water_loss'],
                'cold_water_total' => $month['cold_water_total'],
                'hot_water_total' => $month['hot_water_total'],
                'apartment_cold_consumption' => $month['apartment_cold_consumption'],
                'apartment_hot_consumption' => $month['apartment_hot_consumption'],
                'apartment_total_consumption' => $month['apartment_total_consumption'],
                'apartment_count' => $month['apartment_count'],
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
            return [
                $item['cold_water_loss'],
                $item['hot_water_loss'],
                $item['cold_water_total'],
                $item['hot_water_total']
            ];
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

        // Get readings within the date range to identify months with data
        $readings = Reading::where('reading_date', '>=', $startDate)
            ->select(
                DB::raw('EXTRACT(YEAR FROM reading_date) as year'),
                DB::raw('EXTRACT(MONTH FROM reading_date) as month')
            )
            ->distinct()
            ->get();

        // Extract unique months with data
        $uniqueMonths = collect();
        foreach ($readings as $reading) {
            $key = $reading->year . '-' . $reading->month;
            if (!$uniqueMonths->contains('key', $key)) {
                $uniqueMonths->push([
                    'key' => $key,
                    'date' => Carbon::createFromDate($reading->year, $reading->month, 1)->format('Y-m-d'),
                    'label' => Carbon::createFromDate($reading->year, $reading->month, 1)->format('M Y'),
                    'year' => (int)$reading->year,
                    'month' => (int)$reading->month,
                ]);
            }
        }

        // Sort months chronologically
        $months = $uniqueMonths->sortBy(function ($month) {
            return $month['year'] * 100 + $month['month']; // Sort by year and month
        })->values()->all();

        // Get all apartments
        $apartments = Apartment::all();

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
            // Get the water meters for this apartment
            $apartmentMeters = $waterMeters[$apartment->id] ?? collect();
            $meterIds = $apartmentMeters->pluck('id')->toArray();
            $hasWaterMeters = count($meterIds) > 0;

            $apartmentData = [
                'id' => $apartment->id,
                'floor' => $apartment->floor,
                'number' => $apartment->number,
                'name' => "Етаж {$apartment->floor}, Ап. {$apartment->number}",
                'owner' => $apartment->owner_name,
                'has_water_meters' => $hasWaterMeters,
                'meter_count' => count($meterIds),
                'readings' => [],
                'latest_reading_date' => null,
                'latest_reading_month' => null,
            ];

            // Track the latest reading date for sorting
            $latestReadingDate = null;

            // Process each month
            foreach ($months as $month) {
                $year = $month['year'];
                $monthNum = $month['month'];
                $monthDate = Carbon::createFromDate($year, $monthNum, 1);

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
                    if (! $uniqueMetersWithReadings->contains($reading->water_meter_id)) {
                        $uniqueMetersWithReadings->push($reading->water_meter_id);
                        $submittedReadings++;

                        // Update latest reading date if this is newer
                        $readingDate = Carbon::parse($reading->reading_date);
                        if ($latestReadingDate === null || $readingDate->gt($latestReadingDate)) {
                            $latestReadingDate = $readingDate;
                            $apartmentData['latest_reading_date'] = $readingDate->format('Y-m-d');
                            $apartmentData['latest_reading_month'] = $readingDate->format('M Y');
                        }
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

        // Sort apartments based on the selected field and direction
        $sortField = $this->sortBy;
        $sortDirection = $this->sortDirection;

        usort($tableData['apartments'], function ($a, $b) use ($sortField, $sortDirection) {
            $valueA = $a[$sortField] ?? '';
            $valueB = $b[$sortField] ?? '';

            // Special case for apartment number - sort by floor first, then by number
            if ($sortField === 'number') {
                // Extract numeric part for comparison
                preg_match('/([^\d]+)(\d+)/', $valueA, $matchesA);
                preg_match('/([^\d]+)(\d+)/', $valueB, $matchesB);

                $prefixA = isset($matchesA[1]) ? trim($matchesA[1]) : '';
                $prefixB = isset($matchesB[1]) ? trim($matchesB[1]) : '';

                $numA = isset($matchesA[2]) ? (int)$matchesA[2] : 0;
                $numB = isset($matchesB[2]) ? (int)$matchesB[2] : 0;

                // Define prefix priority
                $prefixPriority = [
                    'МАГ' => 1,
                    'AT' => 2,
                    'АП' => 3,
                    'АT' => 2,
                    'АТ' => 2,
                    'AP' => 3
                ];

                $priorityA = $prefixPriority[$prefixA] ?? 999;
                $priorityB = $prefixPriority[$prefixB] ?? 999;

                // First compare by prefix priority
                if ($priorityA !== $priorityB) {
                    $comparison = $priorityA <=> $priorityB;
                } else {
                    // Then by numeric part
                    $comparison = $numA <=> $numB;
                }
            } else if ($sortField === 'latest_reading_date') {
                // Handle null dates
                if ($valueA === null && $valueB === null) {
                    $comparison = 0;
                } else if ($valueA === null) {
                    $comparison = -1;
                } else if ($valueB === null) {
                    $comparison = 1;
                } else {
                    $comparison = strcmp($valueA, $valueB);
                }
            } else {
                $comparison = is_numeric($valueA) && is_numeric($valueB)
                    ? $valueA <=> $valueB
                    : strcmp($valueA, $valueB);
            }

            return $sortDirection === 'asc' ? $comparison : -$comparison;
        });

        return $tableData;
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function getMonthsForStats()
    {
        // Get data with proper sorting to ensure months are in chronological order
        $sortedData = collect($this->waterLossData)->sortBy(function($item) {
            return Carbon::parse($item['date']);
        })->values()->toArray();

        $months = [];
        foreach ($sortedData as $item) {
            $months[] = $item['date'];
        }

        return $months;
    }

    public function getStats()
    {
        // Get data with proper sorting to ensure we have the most recent month
        $sortedData = collect($this->waterLossData)->sortBy(function($item) {
            return Carbon::parse($item['date']);
        })->values()->toArray();

        // Get the selected month data or default to the most recent month
        $selectedIndex = $this->selectedStatsMonth;
        $months = $this->getMonthsForStats();
        $reversedMonths = array_reverse($months);
        $selectedMonthLabel = isset($reversedMonths[$selectedIndex]) ? $reversedMonths[$selectedIndex] : (count($reversedMonths) > 0 ? $reversedMonths[0] : null);

        // Find the selected month data
        $selectedMonthData = null;
        $previousMonthData = null;

        foreach ($sortedData as $index => $monthData) {
            if ($monthData['date'] === $selectedMonthLabel) {
                $selectedMonthData = $monthData;
                $previousMonthData = $index > 0 ? $sortedData[$index - 1] : null;
                break;
            }
        }

        // If no month is found, get the latest month
        if (!$selectedMonthData && count($sortedData) > 0) {
            $selectedMonthData = $sortedData[count($sortedData) - 1];
            $previousMonthData = count($sortedData) > 1 ? $sortedData[count($sortedData) - 2] : null;
        }

        // Format numbers and calculate trends
        $stats = [];

        // Combined Cold Water Stats (Column 1)
        if ($selectedMonthData) {
            $centralValue = $selectedMonthData['cold_water_total'];
            $apartmentValue = $selectedMonthData['apartment_cold_consumption'];
            $lossValue = $selectedMonthData['cold_water_loss'];
            
            $prevCentralValue = $previousMonthData ? $previousMonthData['cold_water_total'] : 0;
            $prevApartmentValue = $previousMonthData ? $previousMonthData['apartment_cold_consumption'] : 0;
            $prevLossValue = $previousMonthData ? $previousMonthData['cold_water_loss'] : 0;
            
            $centralTrend = $prevCentralValue > 0 ? 
                round(($centralValue - $prevCentralValue) / $prevCentralValue * 100, 1) : 0;
            $apartmentTrend = $prevApartmentValue > 0 ? 
                round(($apartmentValue - $prevApartmentValue) / $prevApartmentValue * 100, 1) : 0;
            $lossTrend = $prevLossValue > 0 ? 
                round(($lossValue - $prevLossValue) / $prevLossValue * 100, 1) : 0;
            
            // Average trend (for the card's trend indicator)
            $avgTrend = ($centralTrend + $apartmentTrend + $lossTrend) / 3;
            
            $stats[] = [
                'title' => 'Студена вода',
                'value' => number_format($centralValue, 1) . ' / ' . number_format($apartmentValue, 1) . ' / ' . number_format($lossValue, 1),
                'description' => 'Централен / Апартаменти / Загуби',
                'trend' => abs($avgTrend),
                'trendUp' => $avgTrend < 0, // Negative trend is good (less consumption)
                'icon' => 'droplet',
                'column' => 1
            ];
        }
        
        // Combined Hot Water Stats (Column 2)
        if ($selectedMonthData) {
            $centralValue = $selectedMonthData['hot_water_total'];
            $apartmentValue = $selectedMonthData['apartment_hot_consumption'];
            $lossValue = $selectedMonthData['hot_water_loss'];
            
            $prevCentralValue = $previousMonthData ? $previousMonthData['hot_water_total'] : 0;
            $prevApartmentValue = $previousMonthData ? $previousMonthData['apartment_hot_consumption'] : 0;
            $prevLossValue = $previousMonthData ? $previousMonthData['hot_water_loss'] : 0;
            
            $centralTrend = $prevCentralValue > 0 ? 
                round(($centralValue - $prevCentralValue) / $prevCentralValue * 100, 1) : 0;
            $apartmentTrend = $prevApartmentValue > 0 ? 
                round(($apartmentValue - $prevApartmentValue) / $prevApartmentValue * 100, 1) : 0;
            $lossTrend = $prevLossValue > 0 ? 
                round(($lossValue - $prevLossValue) / $prevLossValue * 100, 1) : 0;
            
            // Average trend (for the card's trend indicator)
            $avgTrend = ($centralTrend + $apartmentTrend + $lossTrend) / 3;
            
            $stats[] = [
                'title' => 'Топла вода',
                'value' => number_format($centralValue, 1) . ' / ' . number_format($apartmentValue, 1) . ' / ' . number_format($lossValue, 1),
                'description' => 'Централен / Апартаменти / Загуби',
                'trend' => abs($avgTrend),
                'trendUp' => $avgTrend < 0, // Negative trend is good (less consumption)
                'icon' => 'fire',
                'column' => 2
            ];
        }

        return $stats;
    }

    public function render()
    {
        $user = Auth::user();

        // Get apartments associated with this user
        $apartments = $user->apartments;

        // Initialize selectedApartmentId if not set and user has apartments
        if (! $this->selectedApartmentId && $apartments->count() > 0) {
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
            'readingsTableData' => $readingsTableData,
        ]);
    }
}
