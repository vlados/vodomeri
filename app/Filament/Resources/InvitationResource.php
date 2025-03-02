<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvitationResource\Pages;
use App\Models\Invitation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Invitations';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('apartment_id')
                    ->relationship('apartment', 'number')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Code will be automatically generated')
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditInvitation),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->required()
                    ->default(now()->addDays(7)),
                Forms\Components\DateTimePicker::make('used_at')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditInvitation),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('used_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not used yet'),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->state(function ($record) {
                        if ($record->used_at) {
                            return 'used';
                        }

                        if ($record->expires_at->isPast()) {
                            return 'expired';
                        }

                        return 'active';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'used' => 'success',
                            'active' => 'primary',
                            'expired' => 'danger',
                        };
                    })
                    ->icon(function ($state) {
                        return match ($state) {
                            'used' => 'heroicon-o-check-circle',
                            'active' => 'heroicon-o-envelope',
                            'expired' => 'heroicon-o-x-circle',
                        };
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'used' => 'Used',
                        'expired' => 'Expired',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'active' => $query->whereNull('used_at')->where('expires_at', '>', now()),
                            'used' => $query->whereNotNull('used_at'),
                            'expired' => $query->whereNull('used_at')->where('expires_at', '<', now()),
                        };
                    }),
                Tables\Filters\SelectFilter::make('apartment_id')
                    ->relationship('apartment', 'number')
                    ->label('Apartment'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => is_null($record->used_at) && ! $record->expires_at->isPast())
                    ->action(function ($record) {
                        $record->sendInvitationEmail();

                        // Show notification
                        Notification::make()
                            ->title('Invitation Sent')
                            ->body('The invitation has been sent to '.$record->email)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('extend')
                    ->label('Extend')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn ($record) => is_null($record->used_at) && $record->expires_at->diffInDays(now()) < 3)
                    ->form([
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('New Expiration Date')
                            ->required()
                            ->default(fn (Invitation $record) => $record->expires_at->addDays(7)),
                    ])
                    ->action(function (Invitation $record, array $data) {
                        $record->update(['expires_at' => $data['expires_at']]);
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
            'index' => Pages\ListInvitations::route('/'),
            'create' => Pages\CreateInvitation::route('/create'),
            'view' => Pages\ViewInvitation::route('/{record}'),
            'edit' => Pages\EditInvitation::route('/{record}/edit'),
        ];
    }
}
