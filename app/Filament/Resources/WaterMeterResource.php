<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaterMeterResource\Pages;
use App\Filament\Resources\WaterMeterResource\RelationManagers;
use App\Models\WaterMeter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WaterMeterResource extends Resource
{
    protected static ?string $model = WaterMeter::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    
    protected static ?string $navigationLabel = 'Water Meters';
    
    protected static ?string $navigationGroup = 'Property Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('apartment_id')
                    ->relationship('apartment', 'number')
                    ->required()
                    ->searchable(),
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
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->step(0.001),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('apartment.number')
                    ->label('Apartment')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hot' => 'danger',
                        'cold' => 'info',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('installation_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('initial_reading')
                    ->numeric(3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReadingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWaterMeters::route('/'),
            'create' => Pages\CreateWaterMeter::route('/create'),
            'view' => Pages\ViewWaterMeter::route('/{record}'),
            'edit' => Pages\EditWaterMeter::route('/{record}/edit'),
        ];
    }
}
