<?php

namespace App\Filament\Exports;

use App\Models\Reading;
use App\Models\WaterMeter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class HotWaterReportExporter extends Exporter
{
    protected static ?string $model = Reading::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('waterMeter.apartment.number')
                ->label('Apartment Number'),
            ExportColumn::make('waterMeter.apartment.owner_name')
                ->label('Owner'),
            ExportColumn::make('waterMeter.serial_number')
                ->label('Serial Number'),
            ExportColumn::make('previous_reading')
                ->label('Old Reading')
                ->state(function (Reading $record): string {
                    // Get the previous reading
                    $previousReading = Reading::where('water_meter_id', $record->water_meter_id)
                        ->where('reading_date', '<', $record->reading_date)
                        ->orderBy('reading_date', 'desc')
                        ->first();

                    // If no previous reading, use initial reading
                    if (! $previousReading) {
                        $waterMeter = WaterMeter::find($record->water_meter_id);

                        return number_format($waterMeter->initial_reading, 3);
                    }

                    return number_format($previousReading->value, 3);
                }),
            ExportColumn::make('value')
                ->label('New Reading')
                ->formatStateUsing(fn ($state) => number_format($state, 3)),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Get only the latest reading for each hot water meter for the specified month and year
        return parent::getEloquentQuery()
            ->whereHas('waterMeter', function (Builder $query) {
                $query->where('type', 'hot');
            })
            ->whereRaw('(water_meter_id, reading_date) IN (
                SELECT water_meter_id, MAX(reading_date) 
                FROM readings 
                WHERE YEAR(reading_date) = ? AND MONTH(reading_date) = ?
                GROUP BY water_meter_id
            )', [
                request('year', now()->year),
                request('month', now()->month),
            ])
            ->orderBy('water_meter_id');
    }

    public function getFileName(Export $export): string
    {
        $options = $export->options ?? [];

        $year = $options['year'] ?? now()->format('Y');
        $month = $options['month'] ?? now()->format('m');

        // Ensure month is two digits
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);

        return "HotWaterReport-{$year}-{$month}";
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your hot water report has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
