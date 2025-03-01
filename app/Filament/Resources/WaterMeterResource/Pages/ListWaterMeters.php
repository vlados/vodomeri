<?php

namespace App\Filament\Resources\WaterMeterResource\Pages;

use App\Filament\Resources\WaterMeterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaterMeters extends ListRecords
{
    protected static string $resource = WaterMeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
