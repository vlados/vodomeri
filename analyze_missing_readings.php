<?php

require __DIR__.'/vendor/autoload.php';

use App\Models\Apartment;
use App\Models\Reading;
use App\Models\WaterMeter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Display header
echo "\n";
echo "=================================================================\n";
echo "            APARTMENT READINGS ANALYSIS REPORT                    \n";
echo "=================================================================\n";

// Get date range for analysis
$endDate = Carbon::now()->endOfMonth();
$startDate = $endDate->copy()->subMonths(6)->startOfMonth();
echo "Analyzing readings from " . $startDate->format('M Y') . " to " . $endDate->format('M Y') . "\n\n";

// Generate all months in the period
$months = [];
$currentDate = clone $startDate;
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
$apartments = Apartment::with(['waterMeters', 'waterMeters.readings' => function($query) use ($startDate) {
    $query->where('reading_date', '>=', $startDate);
}])->orderBy('floor')->orderBy('number')->get();

// Summary counters
$totalMissingMonths = 0;
$totalPartialMonths = 0;
$totalCompleteMonths = 0;
$apartmentsWithAllReadings = 0;
$apartmentsWithSomeMissing = 0;
$apartmentsWithAllMissing = 0;

// Analyze each apartment
foreach ($apartments as $apartment) {
    $hotMeters = $apartment->waterMeters->where('type', 'hot');
    $coldMeters = $apartment->waterMeters->where('type', 'cold');
    
    echo "Apartment Floor {$apartment->floor}, No. {$apartment->number}: " . 
         $hotMeters->count() . " hot meter(s), " . 
         $coldMeters->count() . " cold meter(s)\n";
    
    $missingMonths = 0;
    $partialMonths = 0;
    $completeMonths = 0;
    
    // Check each month
    foreach ($months as $month) {
        $year = $month['year'];
        $monthNum = $month['month'];
        
        $hotReadings = 0;
        $coldReadings = 0;
        
        // Check hot water meters
        foreach ($hotMeters as $meter) {
            $hasReading = $meter->readings->contains(function($reading) use ($year, $monthNum) {
                return Carbon::parse($reading->reading_date)->year == $year &&
                       Carbon::parse($reading->reading_date)->month == $monthNum;
            });
            
            if ($hasReading) {
                $hotReadings++;
            }
        }
        
        // Check cold water meters
        foreach ($coldMeters as $meter) {
            $hasReading = $meter->readings->contains(function($reading) use ($year, $monthNum) {
                return Carbon::parse($reading->reading_date)->year == $year &&
                       Carbon::parse($reading->reading_date)->month == $monthNum;
            });
            
            if ($hasReading) {
                $coldReadings++;
            }
        }
        
        // Determine status
        $status = 'none'; // Default status
        
        if ($hotMeters->count() > 0 && $coldMeters->count() > 0) {
            // If apartment has both types of meters
            if ($hotReadings == $hotMeters->count() && $coldReadings == $coldMeters->count()) {
                $status = 'complete';
            } elseif ($hotReadings > 0 || $coldReadings > 0) {
                $status = 'partial';
            }
        } elseif ($hotMeters->count() > 0 && $hotReadings == $hotMeters->count()) {
            $status = 'complete'; // All hot meters have readings
        } elseif ($coldMeters->count() > 0 && $coldReadings == $coldMeters->count()) {
            $status = 'complete'; // All cold meters have readings
        }
        
        // Update counters
        if ($status === 'none') {
            $missingMonths++;
        } elseif ($status === 'partial') {
            $partialMonths++;
        } elseif ($status === 'complete') {
            $completeMonths++;
        }
        
        echo "  - " . str_pad($month['label'], 8) . ": ";
        
        if ($status === 'complete') {
            echo "\033[32mComplete\033[0m";  // Green
        } elseif ($status === 'partial') {
            echo "\033[33mPartial\033[0m";   // Yellow
        } else {
            echo "\033[31mMissing\033[0m";   // Red
        }
        
        echo " (Hot: $hotReadings/{$hotMeters->count()}, Cold: $coldReadings/{$coldMeters->count()})\n";
    }
    
    // Update global counters
    $totalMissingMonths += $missingMonths;
    $totalPartialMonths += $partialMonths;
    $totalCompleteMonths += $completeMonths;
    
    // Count apartment status
    if ($missingMonths === count($months)) {
        $apartmentsWithAllMissing++;
    } elseif ($completeMonths === count($months)) {
        $apartmentsWithAllReadings++;
    } else {
        $apartmentsWithSomeMissing++;
    }
    
    echo "  Summary: " . 
         "\033[32mComplete: $completeMonths\033[0m, " . 
         "\033[33mPartial: $partialMonths\033[0m, " . 
         "\033[31mMissing: $missingMonths\033[0m out of " . 
         count($months) . " months\n\n";
}

// Overall statistics
echo "=================================================================\n";
echo "                  OVERALL STATISTICS                              \n";
echo "=================================================================\n";
echo "Total Apartments: " . $apartments->count() . "\n";
echo "Apartments with all readings complete: $apartmentsWithAllReadings\n";
echo "Apartments with some readings missing: $apartmentsWithSomeMissing\n";
echo "Apartments with all readings missing: $apartmentsWithAllMissing\n\n";

$totalMonths = count($months) * $apartments->count();
$percentComplete = round(($totalCompleteMonths / $totalMonths) * 100, 1);
$percentPartial = round(($totalPartialMonths / $totalMonths) * 100, 1);
$percentMissing = round(($totalMissingMonths / $totalMonths) * 100, 1);

echo "Total month-apartment combinations: $totalMonths\n";
echo "Complete readings: $totalCompleteMonths ($percentComplete%)\n";
echo "Partial readings: $totalPartialMonths ($percentPartial%)\n";
echo "Missing readings: $totalMissingMonths ($percentMissing%)\n";
echo "=================================================================\n";