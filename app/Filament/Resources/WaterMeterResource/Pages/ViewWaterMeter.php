<?php

namespace App\Filament\Resources\WaterMeterResource\Pages;

use App\Filament\Resources\WaterMeterResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWaterMeter extends ViewRecord
{
    protected static string $resource = WaterMeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('addReading')
                ->label('Add Reading')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('reading_date')
                        ->label('Date')
                        ->required()
                        ->default(now()),
                    \Filament\Forms\Components\TextInput::make('value')
                        ->label('Reading Value')
                        ->required()
                        ->numeric()
                        ->step(0.001),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->record->readings()->create([
                        'user_id' => auth()->id(),
                        'reading_date' => $data['reading_date'],
                        'value' => $data['value'],
                        'notes' => $data['notes'],
                    ]);
                }),
        ];
    }
}
