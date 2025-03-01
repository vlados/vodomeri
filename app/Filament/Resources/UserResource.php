<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Users';
    
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => !empty($state))
                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('Roles'),
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
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple(),
                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),
                Tables\Filters\Filter::make('unverified')
                    ->query(fn (Builder $query) => $query->whereNull('email_verified_at')),
            ])
            ->actions([
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
            RelationManagers\ApartmentsRelationManager::class,
            RelationManagers\ReadingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
