<?php

namespace App\Filament\Resources\ApartmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->required()
                    ->default(now()->addDays(7)),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => is_null($record->used_at) && ! $record->expires_at->isPast())
                    ->action(function ($record) {
                        $record->sendInvitationEmail();

                        // Show notification
                        Filament\Notifications\Notification::make()
                            ->title('Invitation Sent')
                            ->body('The invitation has been sent to '.$record->email)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
