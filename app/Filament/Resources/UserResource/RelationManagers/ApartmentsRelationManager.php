<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'apartments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('apartment_id')
                    ->relationship('apartment', 'number')
                    ->required()
                    ->searchable(),
                Forms\Components\Toggle::make('is_primary')
                    ->label('Primary Contact')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('floor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Contact')
                            ->default(false),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Role')
                    ->modalHeading('Edit Apartment Role')
                    ->modalSubmitActionLabel('Update')
                    ->form([
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Contact')
                            ->default(false),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}