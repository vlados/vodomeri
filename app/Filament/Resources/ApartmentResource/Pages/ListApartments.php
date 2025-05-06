<?php

namespace App\Filament\Resources\ApartmentResource\Pages;

use App\Filament\Resources\ApartmentResource;
use App\Models\Apartment;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ListApartments extends ListRecords
{
    protected static string $resource = ApartmentResource::class;

    public bool $isEditable = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('bulkCreate')
                ->label('Bulk Create')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('count')
                        ->label('Number of apartments to create')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(5),
                    Forms\Components\TextInput::make('prefix')
                        ->label('Apartment number prefix')
                        ->required()
                        ->placeholder('e.g., A, 101, etc.'),
                    Forms\Components\TextInput::make('start_floor')
                        ->label('Starting floor')
                        ->required()
                        ->numeric()
                        ->default(1),
                ])
                ->action(function (array $data): void {
                    $count = (int) $data['count'];
                    $prefix = $data['prefix'];
                    $floor = (int) $data['start_floor'];

                    for ($i = 1; $i <= $count; $i++) {
                        Apartment::create([
                            'number' => $prefix.$i,
                            'floor' => $floor,
                            'notes' => 'Bulk created apartment',
                        ]);

                        // Increment floor every 4 apartments (adjustable as needed)
                        if ($i % 4 === 0) {
                            $floor++;
                        }
                    }

                    $this->notify('success', "{$count} apartments created with prefix '{$prefix}'");
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $columns = $this->isEditable
            ? [
                Tables\Columns\TextInputColumn::make('number')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('floor')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('owner_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
            ]
            : [
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('floor')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
            ];

        // Common columns for both modes
        $commonColumns = [
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        return parent::table($table)
            ->columns(array_merge($columns, $commonColumns))
            ->headerActions([
                Tables\Actions\Action::make('toggleEditable')
                    ->label(fn () => $this->isEditable ? 'Disable Editing' : 'Enable Editing')
                    ->icon(fn () => $this->isEditable ? 'heroicon-o-lock-closed' : 'heroicon-o-pencil')
                    ->action(function () {
                        $this->isEditable = ! $this->isEditable;
                        $this->resetTable();
                    }),
            ]);
    }
}
