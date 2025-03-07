<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaterMeterResource\Pages;
use App\Filament\Resources\WaterMeterResource\RelationManagers;
use App\Models\WaterMeter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->relationship(
                        'apartment',
                        'number',
                        fn (Builder $query) => $query->orderBy('floor')->orderBy('number')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "Floor {$record->floor}, Apt {$record->number}")
                    ->required(fn (callable $get) => ! in_array($get('type'), ['central-hot', 'central-cold']))
                    ->searchable()
                    ->visible(fn (callable $get) => ! in_array($get('type'), ['central-hot', 'central-cold'])),
                Forms\Components\TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'hot' => 'Hot Water',
                        'cold' => 'Cold Water',
                        'central-hot' => 'Central Hot Water Meter',
                        'central-cold' => 'Central Cold Water Meter',
                    ])
                    ->reactive(),
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
                Tables\Columns\TextColumn::make('apartment.id')
                    ->label('Apartment')
                    ->formatStateUsing(function ($state, WaterMeter $record) {
                        if ($record->isCentralHot()) {
                            return 'Central Hot Water Meter';
                        }

                        if ($record->isCentralCold()) {
                            return 'Central Cold Water Meter';
                        }

                        if ($record->apartment) {
                            return "Floor {$record->apartment->floor}, Apt {$record->apartment->number}";
                        }

                        return '';
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->copyable()
                    ->copyMessage('Serial number copied')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hot' => 'danger',
                        'cold' => 'info',
                        'central-hot' => 'danger',
                        'central-cold' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hot' => 'Hot Water',
                        'cold' => 'Cold Water',
                        'central-hot' => 'Central Hot Water',
                        'central-cold' => 'Central Cold Water',
                        default => $state,
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
                Tables\Filters\SelectFilter::make('apartment_id')
                    ->relationship(
                        'apartment',
                        'number',
                        fn (Builder $query) => $query->orderBy('floor')->orderBy('number')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "Етаж {$record->floor}, {$record->number}")
                    ->preload()
                    ->label('Апартамент')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'hot' => 'Hot Water',
                        'cold' => 'Cold Water',
                        'central-hot' => 'Central Hot Water',
                        'central-cold' => 'Central Cold Water',
                    ])
                    ->label('Meter Type'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('addReading')
                    ->label('Add Reading')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('reading_date')
                            ->label('Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('value')
                            ->label('Reading Value')
                            ->required()
                            ->numeric()
                            ->step(0.001),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(255),
                    ])
                    ->action(function (WaterMeter $record, array $data): void {
                        $record->readings()->create([
                            'user_id' => auth()->id(),
                            'reading_date' => $data['reading_date'],
                            'value' => $data['value'],
                            'notes' => $data['notes'],
                        ]);
                    }),
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
