<?php

namespace App\Filament\Resources\WaterMeterResource\Pages;

use App\Filament\Resources\WaterMeterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWaterMeter extends EditRecord
{
    protected static string $resource = WaterMeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
