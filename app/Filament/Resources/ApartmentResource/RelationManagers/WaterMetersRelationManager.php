<?php

namespace App\Filament\Resources\ApartmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WaterMetersRelationManager extends RelationManager
{
    protected static string $relationship = 'waterMeters';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'hot' => 'Hot Water',
                        'cold' => 'Cold Water',
                    ]),
                Forms\Components\TextInput::make('location')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('installation_date'),
                Forms\Components\TextInput::make('initial_reading')
                    ->numeric()
                    ->default(0)
                    ->step(0.001),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hot', 'central-hot' => 'danger',
                        'cold', 'central-cold' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('installation_date')
                    ->date(),
                Tables\Columns\TextColumn::make('initial_reading')
                    ->numeric(3),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
