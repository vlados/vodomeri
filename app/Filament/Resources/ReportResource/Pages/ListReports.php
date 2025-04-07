<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use App\Models\Reading;
use App\Models\WaterMeter;
use App\Models\Apartment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Auth;

class ListReports extends Page
{
    protected static string $resource = ReportResource::class;

    protected static string $view = 'filament.resources.report-resource.pages.list-reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Създаване на справка')
                ->form(fn (Action $action): array => [
                    Select::make('report_type')
                        ->label('Тип справка')
                        ->options([
                            'hot_water' => 'Гореща вода',
                        ])
                        ->required(),

                    Select::make('month')
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

                    Select::make('year')
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
                ->action(function (array $data) {
                    return $this->generateReport($data);
                })
        ];
    }
    
    public function generateReport(array $data)
    {
        $reportType = $data['report_type'];
        $year = $data['year'];
        $month = $data['month'];
        
        // Get all apartments with hot water meters
        $apartments = Apartment::with(['waterMeters' => function ($query) use ($reportType) {
            if ($reportType === 'hot_water') {
                $query->where('type', 'hot');
            } elseif ($reportType === 'cold_water') {
                $query->where('type', 'cold');
            }
        }])
        ->get();
        
        // Sort apartments the same way as in the dashboard
        $apartments = $apartments->sort(function ($a, $b) {
            // First, sort by floor
            $floorComparison = $a->floor <=> $b->floor;
            if ($floorComparison !== 0) {
                return $floorComparison;
            }
            
            // Extract numeric part for comparison for apartment number
            $numberA = $a->number;
            $numberB = $b->number;
            
            preg_match('/([^\d]+)(\d+)/', $numberA, $matchesA);
            preg_match('/([^\d]+)(\d+)/', $numberB, $matchesB);
            
            $prefixA = isset($matchesA[1]) ? trim($matchesA[1]) : '';
            $prefixB = isset($matchesB[1]) ? trim($matchesB[1]) : '';
            
            $numA = isset($matchesA[2]) ? (int)$matchesA[2] : 0;
            $numB = isset($matchesB[2]) ? (int)$matchesB[2] : 0;
            
            // Define prefix priority
            $prefixPriority = [
                'МАГ' => 1,
                'AT' => 2,
                'АП' => 3,
                'АT' => 2,
                'АТ' => 2,
                'AP' => 3
            ];
            
            $priorityA = $prefixPriority[$prefixA] ?? 999;
            $priorityB = $prefixPriority[$prefixB] ?? 999;
            
            // First compare by prefix priority
            if ($priorityA !== $priorityB) {
                return $priorityA <=> $priorityB;
            }
            
            // Then by numeric part
            return $numA <=> $numB;
        });
        
        // Create a new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Format month and year for headers
        $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        $formattedMonthYear = "{$formattedMonth}.{$year}";
        
        // Get Bulgarian month name
        $bgMonths = [
            1 => 'Януари',
            2 => 'Февруари',
            3 => 'Март',
            4 => 'Април',
            5 => 'Май',
            6 => 'Юни',
            7 => 'Юли',
            8 => 'Август',
            9 => 'Септември',
            10 => 'Октомври',
            11 => 'Ноември',
            12 => 'Декември',
        ];
        
        $bgMonth = $bgMonths[(int)$month];
        
        // Add title to the report
        $sheet->setCellValue('A1', ($reportType === 'hot_water' ? 'Гореща вода' : 'Студена вода') . ' - ' . $bgMonth . ' ' . $year);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set the headers in Bulgarian
        $sheet->setCellValue('A2', 'Апартамент №');
        $sheet->setCellValue('B2', 'Собственик');
        $sheet->setCellValue('C2', 'Сериен номер');
        $sheet->setCellValue('D2', 'Старо показание');
        $sheet->setCellValue('E2', 'Ново показание');
        $sheet->setCellValue('F2', 'Консумация');
        
        // Style the headers
        $headerStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        
        $sheet->getStyle('A2:F2')->applyFromArray($headerStyle);
        
        // Populate data
        $row = 3; // Start from row 3 because we added a title row
        $lastApartmentNumber = null;
        $lastOwnerName = null;
        $mergeStartRow = 3;
        
        
        foreach ($apartments as $apartment) {
            $filteredMeters = $apartment->waterMeters->filter(function($meter) use ($reportType) {
                if ($reportType === 'hot_water') {
                    return $meter->type === 'hot';
                } elseif ($reportType === 'cold_water') {
                    return $meter->type === 'cold';
                }
                return false;
            });
            
            if ($filteredMeters->isEmpty()) {
                continue; // Skip apartments with no matching meters
            }
            
            $apartmentHasData = false;
            $currentMergeStartRow = $row;
            
            foreach ($filteredMeters as $meter) {
                // Get the latest reading for the specified month and year
                $reading = $meter->readings()
                    ->whereYear('reading_date', $year)
                    ->whereMonth('reading_date', $month)
                    ->orderBy('reading_date', 'desc')
                    ->first();
                
                // Initialize values
                $oldValue = $meter->initial_reading;
                $newValue = null;
                $consumption = null;
                $hasReading = false;
                
                // Get the previous reading
                if ($reading) {
                    $previousReading = $meter->readings()
                        ->where('reading_date', '<', $reading->reading_date)
                        ->orderBy('reading_date', 'desc')
                        ->first();
                    
                    $oldValue = $previousReading ? $previousReading->value : $meter->initial_reading;
                    $newValue = $reading->value;
                    $consumption = $reading->value - $oldValue;
                    $hasReading = true;
                } else {
                    // Get latest reading before this month as the old value
                    $latestReading = $meter->readings()
                        ->where(function ($query) use ($year, $month) {
                            $query->whereYear('reading_date', '<', $year)
                                  ->orWhere(function ($q) use ($year, $month) {
                                      $q->whereYear('reading_date', '=', $year)
                                        ->whereMonth('reading_date', '<', $month);
                                  });
                        })
                        ->orderBy('reading_date', 'desc')
                        ->first();
                    
                    if ($latestReading) {
                        $oldValue = $latestReading->value;
                    }
                }
                
                // If apartment number changes, handle merging for the previous apartment
                if ($lastApartmentNumber !== null && $lastApartmentNumber != $apartment->number) {
                    if ($row > $mergeStartRow) {
                        $sheet->mergeCells("A{$mergeStartRow}:A" . ($row - 1));
                        $sheet->mergeCells("B{$mergeStartRow}:B" . ($row - 1));
                        
                        // Center align vertically
                        $sheet->getStyle("A{$mergeStartRow}:B" . ($row - 1))->getAlignment()
                            ->setVertical(Alignment::VERTICAL_CENTER);
                    }
                    $mergeStartRow = $row;
                }
                
                // Set cell values
                $sheet->setCellValue("A{$row}", "Етаж {$apartment->floor}, {$apartment->number}");
                $sheet->setCellValue("B{$row}", $apartment->owner_name);
                $sheet->setCellValue("C{$row}", $meter->serial_number);
                $sheet->setCellValue("D{$row}", number_format($oldValue, 3));
                
                if ($hasReading) {
                    $sheet->setCellValue("E{$row}", number_format($newValue, 3));
                    $sheet->setCellValue("F{$row}", number_format($consumption, 3));
                } else {
                    $sheet->setCellValue("E{$row}", "Няма");
                    $sheet->setCellValue("F{$row}", "");
                    
                    // Apply light gray background to cells with no reading
                    $sheet->getStyle("E{$row}:F{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F3F4F6');
                }
                
                $lastApartmentNumber = $apartment->number;
                $lastOwnerName = $apartment->owner_name;
                $row++;
                $apartmentHasData = true;
            }
        }
        
        // Handle merging for the last apartment
        if ($mergeStartRow < ($row - 1)) {
            $sheet->mergeCells("A{$mergeStartRow}:A" . ($row - 1));
            $sheet->mergeCells("B{$mergeStartRow}:B" . ($row - 1));
            
            // Center align vertically
            $sheet->getStyle("A{$mergeStartRow}:B" . ($row - 1))->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
        
        // Auto size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Center apartment numbers and align values to the right
        $sheet->getStyle('A3:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B3:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C3:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3:D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E3:E' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F3:F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Center align header cells
        $sheet->getStyle('A2:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Add borders to all cells
        $sheet->getStyle('A1:F' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Bold the consumption column to highlight it
        $sheet->getStyle('F3:F' . ($row - 1))->getFont()->setBold(true);
        
        // Add background color to header cells
        $sheet->getStyle('A2:F2')->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');
        
        // Format month to include leading zero
        $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Determine report type name for the filename
        $reportTypeName = $reportType === 'hot_water' ? 'ГорещаВода' : 'СтуденаВода';
        
        // Create the writer and output the file
        $writer = new Xlsx($spreadsheet);
        $filename = "Справка_{$reportTypeName}_{$year}_{$formattedMonth}.xlsx";
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'report');
        $writer->save($tempFile);
        
        // Return the file as a download
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}