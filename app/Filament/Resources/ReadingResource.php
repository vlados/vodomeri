<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadingResource\Pages;
use App\Models\Reading;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\ReadingExporter;
use Illuminate\Database\Eloquent\Builder;

class ReadingResource extends Resource
{
    protected static ?string $model = Reading::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Meter Readings';

    protected static ?string $navigationGroup = 'Readings Management';

    public static function form(Form $form): Form
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
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            // Get the water meter details
                            $waterMeter = \App\Models\WaterMeter::find($state);
                            if ($waterMeter) {
                                $set('water_meter_serial', $waterMeter->serial_number);
                                $set('apartment_id', $waterMeter->apartment_id);
                            }
                        }
                    }),
                Forms\Components\Hidden::make('water_meter_serial'),
                Forms\Components\Hidden::make('apartment_id'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
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
                    ->imageEditor()
                    ->imagePreviewHeight('250')
                    ->panelAspectRatio('16:9')
                    ->panelLayout('integrated')
                    ->saveUploadedFileUsing(function ($file, callable $get) {
                        $waterId = $get('water_meter_id');
                        $readingDate = $get('reading_date');
                        
                        if (!$waterId || !$readingDate) {
                            return $file->store('reading-photos-temp', 'public');
                        }
                        
                        // Use the centralized method for storing photos
                        return \App\Models\Reading::storeUploadedPhoto($file, $waterId, $readingDate);
                    })
                    ->required()
                    ->maxSize(5120) // 5MB
                    ->helperText('Upload a clear photo of the meter reading display showing all digits (maximum 5MB)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('waterMeter.serial_number')
                    ->label('Meter Serial')
                    ->searchable(),
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
                    ->disk('public')
                    ->visibility('public')
                    ->size(100)
                    ->square(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ReadingExporter::class)
                    ->options(function () {
                        // Get filter values from the current request
                        $request = request();
                        $tableFilters = $request->get('tableFilters', []);
                        
                        $month = $tableFilters['month'] ?? null;
                        $year = $tableFilters['year'] ?? null;
                        
                        if (is_array($month)) {
                            $month = $month['value'] ?? null;
                        }
                        
                        if (is_array($year)) {
                            $year = $year['value'] ?? null;
                        }
                        
                        return [
                            'month' => $month,
                            'year' => $year,
                        ];
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('month')
                    ->options([
                        '01' => 'January',
                        '02' => 'February',
                        '03' => 'March',
                        '04' => 'April',
                        '05' => 'May',
                        '06' => 'June',
                        '07' => 'July',
                        '08' => 'August',
                        '09' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $month): Builder => $query->whereMonth('reading_date', $month)
                        );
                    }),
                Tables\Filters\SelectFilter::make('year')
                    ->options([
                        '2024' => '2024',
                        '2025' => '2025',
                        '2026' => '2026',
                        '2027' => '2027',
                    ])
                    ->default('2025')
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $year): Builder => $query->whereYear('reading_date', $year)
                        );
                    }),
                Tables\Filters\SelectFilter::make('water_meter_id')
                    ->relationship('waterMeter', 'serial_number')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        if ($record->isCentral()) {
                            return 'Central Building Meter';
                        }

                        $type = $record->type === 'hot' ? 'Hot' : 'Cold';

                        return "Apt {$record->apartment->number} - {$type} Water";
                    })
                    ->label('Water Meter'),
                Tables\Filters\SelectFilter::make('apartment')
                    ->relationship('waterMeter.apartment', 'number')
                    ->label('Apartment'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReadings::route('/'),
            'create' => Pages\CreateReading::route('/create'),
            'view' => Pages\ViewReading::route('/{record}'),
            'edit' => Pages\EditReading::route('/{record}/edit'),
        ];
    }
}
