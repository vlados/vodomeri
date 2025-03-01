<?php

namespace App\Filament\Resources\WaterMeterResource\RelationManagers;

use App\Models\Reading;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('reading_date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('value')
                    ->label('Reading Value')
                    ->required()
                    ->numeric()
                    ->step(0.001),
                Forms\Components\FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()
                    ->directory('reading-photos')
                    ->maxSize(5120) // 5MB
                    ->helperText('Upload a photo of the meter (maximum 5MB)'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reading_date')
            ->columns([
                Tables\Columns\TextColumn::make('reading_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Reading')
                    ->numeric(3),
                Tables\Columns\TextColumn::make('consumption')
                    ->numeric(3),
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
                    ->label('Submitted By'),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function (Reading $record) {
                                $record->update(['status' => 'approved']);
                            });
                        }),
                ]),
            ]);
    }
}