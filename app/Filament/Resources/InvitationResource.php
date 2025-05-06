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
                    ->required()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->options(function () {
                        $apartments = \App\Models\Apartment::orderBy('floor')->orderBy('number')->get();

                        $grouped = [
                            'Available' => [],
                            'No Email Set' => [],
                            'Has Active Invitation' => [],
                            'Already Registered' => [],
                        ];

                        foreach ($apartments as $apartment) {
                            $baseLabel = "Apt {$apartment->number} - Floor {$apartment->floor}".
                                ($apartment->owner_name ? " ({$apartment->owner_name})" : '');

                            // Check if apartment has email
                            if (! $apartment->email) {
                                $grouped['No Email Set'][$apartment->id] = $baseLabel;

                                continue;
                            }

                            // Check if there's an active invitation for this email
                            $activeInvitation = \App\Models\Invitation::where('email', $apartment->email)
                                ->whereNull('used_at')
                                ->where('expires_at', '>', now())
                                ->first();

                            if ($activeInvitation) {
                                $grouped['Has Active Invitation'][$apartment->id] = $baseLabel;

                                continue;
                            }

                            // Check if there's a used invitation for this email
                            $usedInvitation = \App\Models\Invitation::where('email', $apartment->email)
                                ->whereNotNull('used_at')
                                ->first();

                            if ($usedInvitation) {
                                $grouped['Already Registered'][$apartment->id] = $baseLabel;

                                continue;
                            }

                            // Available for invitation
                            $grouped['Available'][$apartment->id] = $baseLabel;
                        }

                        // Remove empty groups
                        foreach ($grouped as $key => $group) {
                            if (empty($group)) {
                                unset($grouped[$key]);
                            }
                        }

                        return $grouped;
                    })
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($state) {
                            // Get the apartment model
                            $apartment = \App\Models\Apartment::find($state);

                            // If apartment has an email, set it
                            if ($apartment && $apartment->email) {
                                $set('email', $apartment->email);

                                // Check if there's an active invitation for this email
                                $activeInvitation = \App\Models\Invitation::where('email', $apartment->email)
                                    ->whereNull('used_at')
                                    ->where('expires_at', '>', now())
                                    ->first();

                                if ($activeInvitation) {
                                    Notification::make()
                                        ->title('Warning')
                                        ->body("This email already has an active invitation that expires on {$activeInvitation->expires_at->format('M d, Y')}.")
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                }

                                // Check if there's a used invitation for this email
                                $usedInvitation = \App\Models\Invitation::where('email', $apartment->email)
                                    ->whereNotNull('used_at')
                                    ->first();

                                if ($usedInvitation) {
                                    Notification::make()
                                        ->title('Info')
                                        ->body("This email has already registered on {$usedInvitation->used_at->format('M d, Y')}.")
                                        ->info()
                                        ->persistent()
                                        ->send();
                                }
                            } elseif ($apartment && ! $apartment->email) {
                                Notification::make()
                                    ->title('Warning')
                                    ->body('This apartment does not have an email address set. Please enter an email manually.')
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }
                        }
                    }),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Code will be automatically generated')
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditInvitation),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->helperText('Email is automatically filled from apartment if available, but can be changed.')
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($state) {
                            // Check if there's an active invitation for this email
                            $activeInvitation = \App\Models\Invitation::where('email', $state)
                                ->whereNull('used_at')
                                ->where('expires_at', '>', now())
                                ->first();

                            if ($activeInvitation) {
                                Notification::make()
                                    ->title('Warning')
                                    ->body("This email already has an active invitation that expires on {$activeInvitation->expires_at->format('M d, Y')}.")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            // Check if there's a used invitation for this email
                            $usedInvitation = \App\Models\Invitation::where('email', $state)
                                ->whereNotNull('used_at')
                                ->first();

                            if ($usedInvitation) {
                                Notification::make()
                                    ->title('Info')
                                    ->body("This email has already registered on {$usedInvitation->used_at->format('M d, Y')}.")
                                    ->info()
                                    ->persistent()
                                    ->send();
                            }
                        }
                    })
                    ->live(),
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
