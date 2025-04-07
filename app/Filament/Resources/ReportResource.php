<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
// No tables used
use Illuminate\Database\Eloquent\Model;

class ReportResource extends Resource
{
    protected static ?string $model = \App\Models\Report::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Справки';
    
    protected static ?string $pluralModelLabel = 'Справки';
    
    protected static ?string $modelLabel = 'Справка';
    
    protected static ?int $navigationSort = 80;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('report_type')
                            ->label('Тип справка')
                            ->options([
                                'hot_water' => 'Гореща вода',
                                'cold_water' => 'Студена вода',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('month')
                            ->label('Месец')
                            ->options([
                                '1' => 'Януари',
                                '2' => 'Февруари',
                                '3' => 'Март',
                                '4' => 'Април',
                                '5' => 'Май',
                                '6' => 'Юни',
                                '7' => 'Юли',
                                '8' => 'Август',
                                '9' => 'Септември',
                                '10' => 'Октомври',
                                '11' => 'Ноември',
                                '12' => 'Декември',
                            ])
                            ->default(now()->month)
                            ->required(),
                        
                        Forms\Components\Select::make('year')
                            ->label('Година')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = 0; $i <= 5; $i++) {
                                    $year = $currentYear - $i;
                                    $years[$year] = $year;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
    
    // No database table for this resource, so no table method is needed
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return 'New';
    }
    
    public static function canDelete(Model $record): bool
    {
        return false;
    }
}