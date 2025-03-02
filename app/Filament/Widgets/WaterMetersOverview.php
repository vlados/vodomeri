<?php

namespace App\Filament\Widgets;

use App\Models\Apartment;
use App\Models\Reading;
use App\Models\WaterMeter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WaterMetersOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMeters = WaterMeter::count();
        $totalApartments = Apartment::count();
        $totalReadingsThisMonth = Reading::whereMonth('reading_date', now()->month)
            ->whereYear('reading_date', now()->year)
            ->count();
            
        return [
            Stat::make('Total Apartments', $totalApartments)
                ->description('Number of registered apartments')
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'),
            
            Stat::make('Total Water Meters', $totalMeters)
                ->description('Total registered meters')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('success'),
                
            Stat::make('This Month\'s Readings', $totalReadingsThisMonth)
                ->description('Readings submitted this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}