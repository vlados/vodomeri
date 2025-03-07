<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('water_meter_id')
                    ->relationship('waterMeter', 'serial_number')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        if ($record->isCentral()) {
                            return "Central Building Meter ({$record->serial_number})";
                        }

                        $type = $record->type === 'hot' ? 'Hot' : 'Cold';

                        return "Apt {$record->apartment->number} - {$type} Water ({$record->serial_number})";
                    })
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('reading_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('value')
                    ->label('Reading Value')
                    ->required()
                    ->numeric()
                    ->step(0.001),
                Forms\Components\TextInput::make('consumption')
                    ->numeric()
                    ->step(0.001)
                    ->disabled()
                    ->helperText('This will be calculated automatically'),
                Forms\Components\FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->reactive()
                    ->saveUploadedFileUsing(function ($file, callable $get) {
                        $waterId = $get('water_meter_id');
                        $readingDate = $get('reading_date') ?? now();
                        
                        if (!$waterId) {
                            return $file->store('reading-photos-temp', 'public');
                        }
                        
                        // Use the centralized method for storing photos
                        return \App\Models\Reading::storeUploadedPhoto($file, $waterId, $readingDate);
                    })
                    ->maxSize(5120), // 5MB
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reading_date')
            ->columns([
                Tables\Columns\TextColumn::make('waterMeter.apartment.number')
                    ->label('Apartment')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('waterMeter.type')
                    ->label('Meter Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hot', 'central-hot' => 'danger',
                        'cold', 'central-cold' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hot' => 'Hot Water',
                        'cold' => 'Cold Water',
                        'central-hot' => 'Central Hot Water',
                        'central-cold' => 'Central Cold Water',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('reading_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Reading')
                    ->numeric(3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('consumption')
                    ->numeric(3)
                    ->sortable(),
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
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
