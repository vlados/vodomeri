<?php

namespace App\Filament\Resources\ReadingResource\Pages;

use App\Filament\Resources\ReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReadings extends ListRecords
{
    protected static string $resource = ReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
