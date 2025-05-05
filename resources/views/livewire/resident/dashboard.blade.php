<div>
    <div class="grid grid-cols-1 gap-y-10">
        <!-- Header with actions -->
        <div class="sm:flex sm:justify-between sm:items-center w-full grid grid-cols-2 gap-4">
            <div class="flex space-x-2 col-span-2">
                @if($apartments->count() > 1)
                    <flux:select variant="listbox" class="sm:max-w-fit" wire:model.live="selectedApartmentId">
                        <x-slot name="trigger">
                            <flux:select.button size="sm">
                                <flux:icon.home variant="micro" class="mr-2 text-zinc-400"/>
                                <flux:select.selected/>
                            </flux:select.button>
                        </x-slot>

                        @foreach($apartments as $apartment)
                            <flux:select.option value="{{ $apartment->id }}">Етаж {{ $apartment->floor }}
                                , {{ $apartment->number }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select variant="listbox" class="sm:max-w-fit" wire:model.live="selectedPeriod">
                    <x-slot name="trigger">
                        <flux:select.button size="sm">
                            <flux:icon.funnel variant="micro" class="mr-2 text-zinc-400"/>
                            <flux:select.selected/>
                        </flux:select.button>
                    </x-slot>

                    <flux:select.option value="last_3_months">Последните 3 месеца</flux:select.option>
                    <flux:select.option value="last_6_months">Последните 6 месеца</flux:select.option>
                    <flux:select.option value="last_12_months">Последните 12 месеца</flux:select.option>
                </flux:select>
            </div>

            <div class="col-span-1 w-full">
                <flux:button href="{{ route('meters.add') }}" size="sm"
                             class="w-full justify-center sm:w-auto sm:justify-start">
                    <x-fas-gauge-high class="w-4 h-4 mr-2" />
                    Нов водомер
                </flux:button>
            </div>
            <div class="col-span-1 w-full ml-auto flex justify-end space-x-2">
                <flux:button href="{{ route('readings.multiple') }}" size="sm" variant="outline"
                             class="w-full justify-center sm:w-auto sm:justify-start">
                    <x-fas-pen-to-square class="w-4 h-4 mr-2" />
                    Стандартен отчет
                </flux:button>
                
                <flux:button href="{{ route('readings.bulk-upload') }}" size="sm" variant="primary"
                             class="w-full justify-center sm:w-auto sm:justify-start">
                    <x-fas-camera class="w-4 h-4 mr-2" />
                    Отчет със снимки
                </flux:button>
            </div>
        </div>
        <!-- Stats Cards -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-900 mb-6 p-4 rounded-lg border border-blue-200 dark:border-gray-700 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div class="flex items-center">
                    <div class="bg-blue-500 text-white p-2 rounded-lg mr-3 shadow-sm">
                        <x-fas-chart-column class="w-5 h-5" />
                    </div>
                    <div>
                        <flux:heading size="lg">Водна статистика</flux:heading>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Потребление и загуби по месеци</p>
                    </div>
                </div>

                <flux:select variant="listbox" class="w-full sm:w-auto max-w-sm" wire:model.live="selectedStatsMonth">
                    <x-slot name="trigger">
                        <flux:select.button size="md" class="w-full sm:w-auto bg-white dark:bg-gray-800 border-blue-200 dark:border-gray-700 shadow-sm">
                            <x-fas-calendar-days class="w-4 h-4 mr-2 text-blue-500" />
                            <flux:select.selected placeholder="Избери месец" />
                        </flux:select.button>
                    </x-slot>
                    @foreach(array_reverse($this->getMonthsForStats()) as $index => $month)
                        <flux:select.option value="{{ $index }}">{{ $month }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 mb-10">
            @php
                $stats = $this->getStats();
                $statsByColumn = [1 => [], 2 => []];

                // Group stats by column
                foreach ($stats as $stat) {
                    if (isset($stat['column']) && $stat['column'] <= 2) {
                        $statsByColumn[$stat['column']][] = $stat;
                    }
                }
            @endphp

            @if(count($stats) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php
                        $cardStyles = [
                            1 => [
                                'card' => 'rounded-xl bg-card text-card-foreground overflow-hidden border-2 border-blue-200',
                                'header' => 'flex flex-col space-y-1.5 p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-blue-900',
                                'icon_container' => 'h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center mr-3',
                                'icon_class' => 'h-6 w-6 text-blue-500 dark:text-blue-300',
                                'stats_icon_bg' => 'h-8 w-8 rounded-full bg-blue-50 dark:bg-blue-900 flex items-center justify-center mr-3',
                                'stats_icon_class' => 'h-4 w-4 text-blue-500 dark:text-blue-300',
                                'stats_percent' => 'text-sm text-blue-500 dark:text-blue-400'
                            ],
                            2 => [
                                'card' => 'rounded-xl bg-card text-card-foreground overflow-hidden border-2 border-red-200',
                                'header' => 'flex flex-col space-y-1.5 p-6 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-950 dark:to-red-900',
                                'icon_container' => 'h-10 w-10 rounded-full bg-red-100 dark:bg-red-800 flex items-center justify-center mr-3',
                                'icon_class' => 'h-6 w-6 text-red-500 dark:text-red-300',
                                'stats_icon_bg' => 'h-8 w-8 rounded-full bg-red-50 dark:bg-red-900 flex items-center justify-center mr-3',
                                'stats_icon_class' => 'h-4 w-4 text-red-500 dark:text-red-300',
                                'stats_percent' => 'text-sm text-red-500 dark:text-red-400'
                            ]
                        ];

                        $titles = [
                            1 => [
                                'title' => 'Cold Water',
                                'subtitle' => 'Consumption metrics'
                            ],
                            2 => [
                                'title' => 'Hot Water',
                                'subtitle' => 'Consumption metrics'
                            ]
                        ];

                        $iconMap = [
                            'droplet' => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"></path>',
                            'fire' => '<path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"></path>'
                        ];
                    @endphp

                    @for ($column = 1; $column <= 2; $column++)
                        @foreach ($statsByColumn[$column] as $stat)
                            <div class="{{ $cardStyles[$column]['card'] }}">
                                <div class="{{ $cardStyles[$column]['header'] }}">
                                    <div class="flex items-center">
                                        <div class="{{ $cardStyles[$column]['icon_container'] }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                 stroke-linejoin="round" class="{{ $cardStyles[$column]['icon_class'] }}">
                                                @php
                                                    $iconName = $stat['icon'];
                                                    if ($iconName === 'tint-slash') $iconName = 'droplet-slash';
                                                    echo $iconMap[$iconName] ?? '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"></path>';
                                                @endphp
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold leading-none tracking-tight">{{ $stat['title'] }}</h3>
                                            <p class="text-sm text-muted-foreground">Потребление и загуби</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 pt-6">
                                    <div class="grid grid-cols-1 gap-6">
                                        @php
                                            // Parse the combined values
                                            $valueParts = explode(' / ', str_replace(' m³', '', $stat['value']));
                                            $centralValue = $valueParts[0] ?? 0;
                                            $apartmentsValue = $valueParts[1] ?? 0;
                                            $lossValue = $valueParts[2] ?? 0;

                                            // Determine loss percentage
                                            $lossPercent = 0;
                                            if ($centralValue > 0) {
                                                $lossPercent = round(($lossValue / $centralValue) * 100, 1);
                                            }
                                        @endphp

                                        <!-- Central Meter -->
                                        <div class="flex items-center justify-between px-2">
                                            <div class="flex items-center">
                                                <div class="{{ $cardStyles[$column]['stats_icon_bg'] }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                         stroke-linecap="round" stroke-linejoin="round"
                                                         class="{{ $cardStyles[$column]['stats_icon_class'] }}">
                                                        <line x1="12" x2="12" y1="20" y2="10"></line>
                                                        <line x1="18" x2="18" y1="20" y2="4"></line>
                                                        <line x1="6" x2="6" y1="20" y2="16"></line>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-muted-foreground">Централен</p>
                                                    <p class="text-2xl font-bold">{{ $centralValue }} m³</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Apartments Sum -->
                                        <div class="flex items-center justify-between px-2">
                                            <div class="flex items-center">
                                                <div class="{{ $cardStyles[$column]['stats_icon_bg'] }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                         stroke-linecap="round" stroke-linejoin="round"
                                                         class="{{ $cardStyles[$column]['stats_icon_class'] }}">
                                                        @php
                                                            echo $iconMap[$stat['icon']] ?? '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"></path>';
                                                        @endphp
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-muted-foreground">Сума апартаменти</p>
                                                    <p class="text-2xl font-bold">{{ $apartmentsValue }} m³</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Loss -->
                                        <div class="flex items-center justify-between px-2">
                                            <div class="flex items-center">
                                                <div class="{{ $cardStyles[$column]['stats_icon_bg'] }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                         stroke-linecap="round" stroke-linejoin="round"
                                                         class="{{ $cardStyles[$column]['stats_icon_class'] }}">
                                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                                                        <path d="M12 9v4"></path>
                                                        <path d="M12 17h.01"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-muted-foreground">Загуби</p>
                                                    <div class="flex items-center">
                                                        <p class="text-2xl font-bold mr-2">{{ $lossValue }} m³</p>
                                                        <span class="{{ $cardStyles[$column]['stats_percent'] }}">({{ $lossPercent }}%)</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endfor
                </div>
            @else
                <div class="col-span-full flex flex-col items-center justify-center py-12 text-center bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-100 dark:border-zinc-700">
                    <div class="rounded-full bg-blue-100 p-4 mb-4 shadow-inner">
                        <x-fas-droplet-slash class="w-12 h-12 text-blue-500" />
                    </div>
                    <p class="text-lg font-medium">Няма данни за потребление</p>
                    <p class="text-sm text-gray-500 mt-1 max-w-md">За да видите статистика, въведете показания на водомерите за поне два последователни месеца.</p>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-y-10">
            <!-- Apartment Readings Table Section -->
            <flux:card class="space-y-6 col-span-1 md:col-span-2">
                <div>
                    <flux:heading size="xl" class="flex items-center">
                        <x-fas-file-chart-column class="w-5 h-5 mr-2 text-gray-600" /> Отчети по апартаменти
                    </flux:heading>
                    <p class="text-sm text-gray-500 mt-1">Състояние на отчетите по месеци и апартаменти</p>
                </div>

                @if(count($readingsTableData['months']) > 0 && count($readingsTableData['apartments']) > 0)
                    <flux:table class="w-full">
                        <flux:table.columns>
                            <flux:table.column pin="left" sortable :sorted="$sortBy === 'floor'"
                                               :direction="$sortDirection" wire:click="sort('floor')">Етаж
                            </flux:table.column>
                            <flux:table.column pin="left" sortable :sorted="$sortBy === 'number'"
                                               :direction="$sortDirection" wire:click="sort('number')">Апартамент
                            </flux:table.column>
                            <flux:table.column pin="left" sortable :sorted="$sortBy === 'owner'"
                                               :direction="$sortDirection" wire:click="sort('owner')">Собственик
                            </flux:table.column>
                            <flux:table.column pin="left" sortable :sorted="$sortBy === 'has_water_meters'"
                                               :direction="$sortDirection" wire:click="sort('has_water_meters')">
                                Водомери
                            </flux:table.column>
                            <flux:table.column pin="left" sortable :sorted="$sortBy === 'latest_reading_date'"
                                               :direction="$sortDirection" wire:click="sort('latest_reading_date')">
                                Последен отчет
                            </flux:table.column>
                            @foreach($readingsTableData['months'] as $month)
                                <flux:table.column class="text-center border-l border-gray-200">
                                    <div
                                        class="w-full text-center">{{ Carbon\Carbon::parse($month['date'])->translatedFormat('F Y') }}</div>
                                </flux:table.column>
                            @endforeach
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($readingsTableData['apartments'] as $apartment)
                                <flux:table.row>
                                    <flux:table.cell pin="left">
                                        {{ $apartment['floor'] }}
                                    </flux:table.cell>
                                    <flux:table.cell pin="left">
                                        {{ $apartment['number'] }}
                                    </flux:table.cell>
                                    <flux:table.cell pin="left" class="truncate">
                                        {{ $apartment['owner'] }}
                                    </flux:table.cell>
                                    <flux:table.cell pin="left">
                                        @if($apartment['has_water_meters'])
                                            <span class="inline-flex items-center text-green-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1"
                                                 viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                            {{ $apartment['meter_count'] }}
                                        </span>
                                        @else
                                            <span class="inline-flex items-center text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1"
                                                 viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                            Няма
                                        </span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell pin="left">
                                        {{ $apartment['latest_reading_month'] ?? 'Няма данни' }}
                                    </flux:table.cell>
                                    @foreach($apartment['readings'] as $reading)
                                        <flux:table.cell class="text-center border-l border-gray-200">
                                            @if($reading['status'] === 'complete')
                                                <flux:badge color="green" size="sm" inset="top bottom">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1"
                                                 viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                            Пълен
                                        </span>
                                                </flux:badge>
                                            @elseif($reading['status'] === 'partial')
                                                <flux:badge color="yellow" size="sm" inset="top bottom" variant="solid">
                                        <span class="flex items-center">
                                            <span
                                                class="text-xs font-medium">{{ $reading['submitted'] }}/{{ $reading['total'] }}</span>
                                        </span>
                                                </flux:badge>
                                            @else
                                                <flux:badge color="red" size="sm" inset="top bottom" variant="solid">
                                        <span class="flex items-center">
                                            <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg"
                                                 viewBox="0 0 128 512">
                                                <path
                                                    d="M96 64c0-17.7-14.3-32-32-32S32 46.3 32 64l0 256c0 17.7 14.3 32 32 32s32-14.3 32-32L96 64zM64 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"
                                                    fill="currentColor"/>
                                            </svg>
                                            Няма
                                        </span>
                                                </flux:badge>
                                            @endif
                                        </flux:table.cell>
                                    @endforeach
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="flex flex-wrap items-center gap-6 text-sm mt-4">
                        <div class="flex items-center">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 mr-2">
                            <x-fas-circle-check class="w-4 h-4 text-green-600" />
                        </span>
                            <span>Всички показания са въведени</span>
                        </div>
                        <div class="flex items-center">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-yellow-100 mr-2">
                            <x-fas-circle-half-stroke class="w-4 h-4 text-yellow-800" />
                        </span>
                            <span>Частични показания (a от b водомера)</span>
                        </div>
                        <div class="flex items-center">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-100 mr-2">
                            <x-fas-circle-exclamation class="w-4 h-4 text-red-800" />
                        </span>
                            <span>Няма показания</span>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center p-8 text-center">
                        <div class="rounded-full bg-blue-50 p-3 mb-4">
                            <x-fas-circle-info class="w-12 h-12 text-blue-500" />
                        </div>
                        <p class="text-lg font-medium">Няма данни за отчети на апартаменти</p>
                        <p class="text-sm text-gray-500 mt-1">За да видите статистика, въведете показания на
                            водомерите.</p>
                    </div>
                @endif
            </flux:card>
        </div>

    </div>
</div>
