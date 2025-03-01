<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReadingResource\Pages;
use App\Filament\Resources\ReadingResource\RelationManagers;
use App\Models\Reading;
use App\Models\WaterMeter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->relationship('waterMeter', function ($query) {
                        return $query->with('apartment')->get()->mapWithKeys(function ($meter) {
                            $type = $meter->type === 'hot' ? 'Hot' : 'Cold';
                            return [$meter->id => "Apt {$meter->apartment->number} - {$type} Water ({$meter->serial_number})"];
                        });
                    })
                    ->searchable()
                    ->required(),
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
                    ->directory('reading-photos')
                    ->maxSize(5120), // 5MB
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
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
                        'hot' => 'danger',
                        'cold' => 'info',
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
                    ->circular(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('water_meter_id')
                    ->relationship('waterMeter', function ($query) {
                        return $query->with('apartment')->get()->mapWithKeys(function ($meter) {
                            $type = $meter->type === 'hot' ? 'Hot' : 'Cold';
                            return [$meter->id => "Apt {$meter->apartment->number} - {$type} Water"];
                        });
                    })
                    ->label('Water Meter'),
                Tables\Filters\SelectFilter::make('apartment')
                    ->relationship('waterMeter.apartment', 'number')
                    ->label('Apartment'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Reading $record) => $record->status === 'pending')
                    ->action(function (Reading $record) {
                        $record->update(['status' => 'approved']);
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Reading $record) => $record->status === 'pending')
                    ->action(function (Reading $record) {
                        $record->update(['status' => 'rejected']);
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
