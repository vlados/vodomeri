<?php

namespace App\Filament\Resources\ReadingResource\Pages;

use App\Filament\Resources\ReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReading extends EditRecord
{
    protected static string $resource = ReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
