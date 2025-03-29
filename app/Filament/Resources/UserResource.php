<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Mail\ReadingReminderMail;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['apartments.waterMeters', 'roles'])
            ->withCount('apartments');
    }

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
                    ->dehydrateStateUsing(fn ($state) => ! empty($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => ! empty($state))
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
                Tables\Columns\TextColumn::make('apartments_count')
                    ->label('Apartments')
                    ->counts('apartments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('water_meters_count')
                    ->label('Water Meters')
                    ->getStateUsing(function (User $record) {
                        return $record->apartments->flatMap->waterMeters->count();
                    }),
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
                Impersonate::make()->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_reading_reminder')
                        ->label('Send Reading Reminder')
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $sent = 0;
                            $failed = 0;
                            $errors = [];

                            foreach ($records as $user) {
                                // Skip users without apartments/water meters
                                $waterMeterCount = $user->apartments->flatMap->waterMeters->count();
                                if ($waterMeterCount === 0) {
                                    $failed++;
                                    $errors[] = "User {$user->name} has no water meters";

                                    continue;
                                }

                                try {
                                    Mail::to($user)->send(new ReadingReminderMail($user));
                                    $sent++;
                                } catch (\Exception $e) {
                                    $failed++;
                                    $errors[] = "Failed to send to {$user->name}: ".$e->getMessage();
                                }
                            }

                            $message = "Reminded {$sent} users to enter readings";
                            if ($failed > 0) {
                                $message .= " ({$failed} failed)";
                            }

                            // Log errors for admin troubleshooting
                            if (! empty($errors)) {
                                \Log::warning('Reading reminder errors: '.implode('; ', array_slice($errors, 0, 3)));

                                if ($sent === 0 && $failed > 0) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Reminders Failed')
                                        ->body($message.'. Check application logs for details.')
                                        ->send();

                                    return;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Reminders Sent')
                                ->body($message)
                                ->send();
                        }),
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
