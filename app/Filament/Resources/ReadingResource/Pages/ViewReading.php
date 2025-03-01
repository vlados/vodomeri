<?php

namespace App\Filament\Resources\ReadingResource\Pages;

use App\Filament\Resources\ReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReading extends ViewRecord
{
    protected static string $resource = ReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
