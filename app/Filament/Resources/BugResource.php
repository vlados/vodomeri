<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BugResource\Pages;
use App\Models\Bug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BugResource extends Resource
{
    protected static ?string $model = Bug::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Доклади за грешки';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Отворен',
                        'in_progress' => 'В процес',
                        'resolved' => 'Решен',
                        'closed' => 'Затворен',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('steps_to_reproduce'),
                Forms\Components\TextInput::make('browser_info')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('screenshot_path')
                    ->image()
                    ->directory('bug-screenshots')
                    ->visibility('public'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Отворен',
                        'in_progress' => 'В процес',
                        'resolved' => 'Решен',
                        'closed' => 'Затворен',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('screenshot_path')
                    ->size(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Отворен',
                        'in_progress' => 'В процес',
                        'resolved' => 'Решен',
                        'closed' => 'Затворен',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_in_progress')
                    ->label('В процес')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->action(fn (Bug $record) => $record->update(['status' => 'in_progress']))
                    ->visible(fn (Bug $record) => $record->status === 'open'),
                Tables\Actions\Action::make('mark_resolved')
                    ->label('Решен')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Bug $record) => $record->update(['status' => 'resolved']))
                    ->visible(fn (Bug $record) => $record->status === 'in_progress'),
                Tables\Actions\Action::make('mark_closed')
                    ->label('Затворен')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->action(fn (Bug $record) => $record->update(['status' => 'closed']))
                    ->visible(fn (Bug $record) => in_array($record->status, ['open', 'in_progress', 'resolved'])),
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
            'index' => Pages\ListBugs::route('/'),
            'create' => Pages\CreateBug::route('/create'),
            'edit' => Pages\EditBug::route('/{record}/edit'),
        ];
    }
}
