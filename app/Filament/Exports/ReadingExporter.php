<?php

namespace App\Filament\Exports;

use App\Models\Reading;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ReadingExporter extends Exporter
{
    protected static ?string $model = Reading::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('waterMeter.apartment.number')
                ->label('Apartment'),
            ExportColumn::make('waterMeter.serial_number')
                ->label('Meter Serial'),
            ExportColumn::make('waterMeter.type')
                ->label('Meter Type')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'hot' => 'Hot Water',
                    'cold' => 'Cold Water',
                    'central-hot' => 'Central Hot Water',
                    'central-cold' => 'Central Cold Water',
                    default => $state,
                }),
            ExportColumn::make('reading_date')
                ->label('Reading Date'),
            ExportColumn::make('value')
                ->label('Reading Value'),
            ExportColumn::make('consumption')
                ->label('Consumption'),
            ExportColumn::make('status')
                ->label('Status'),
            ExportColumn::make('user.name')
                ->label('Submitted By'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('created_at')
                ->label('Created At'),
        ];
    }

    public function getFileName(Export $export): string
    {
        $options = $export->options ?? [];

        $year = $options['year'] ?? now()->format('Y');
        $month = $options['month'] ?? now()->format('m');

        // Ensure month is two digits
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);

        return "PBonchev-{$year}-{$month}";
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your reading export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
