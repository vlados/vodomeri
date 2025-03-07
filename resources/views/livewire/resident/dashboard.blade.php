<div>
    <div class="grid grid-cols-1 gap-10">
        <!-- Header with actions -->
        <div class="sm:flex sm:justify-between sm:items-center w-full grid grid-cols-2 gap-4">
            <div class="flex space-x-2 col-span-2">
                @if($apartments->count() > 1)
                <flux:select variant="listbox" class="sm:max-w-fit" wire:model.live="selectedApartmentId">
                    <x-slot name="trigger">
                        <flux:select.button size="sm">
                            <flux:icon.home variant="micro" class="mr-2 text-zinc-400" />
                            <flux:select.selected />
                        </flux:select.button>
                    </x-slot>

                    @foreach($apartments as $apartment)
                    <flux:select.option value="{{ $apartment->id }}">Етаж {{ $apartment->floor }}, {{ $apartment->number }}</flux:select.option>
                    @endforeach
                </flux:select>
                @endif

                <flux:select variant="listbox" class="sm:max-w-fit" wire:model.live="selectedPeriod">
                    <x-slot name="trigger">
                        <flux:select.button size="sm">
                            <flux:icon.funnel variant="micro" class="mr-2 text-zinc-400" />
                            <flux:select.selected />
                        </flux:select.button>
                    </x-slot>

                    <flux:select.option value="last_3_months">Последните 3 месеца</flux:select.option>
                    <flux:select.option value="last_6_months">Последните 6 месеца</flux:select.option>
                    <flux:select.option value="last_12_months">Последните 12 месеца</flux:select.option>
                </flux:select>
            </div>

            <div class="col-span-1 w-full">
                <flux:button href="{{ route('meters.add') }}" icon="plus" size="sm" class="w-full justify-center sm:w-auto sm:justify-start">Нов водомер</flux:button>
            </div>
            <div class="col-span-1 w-full ml-auto flex justify-end">
                <flux:button href="{{ route('readings.multiple') }}" icon="pencil-square" size="sm" variant="primary" class="w-full justify-center sm:w-auto sm:justify-start">Самоотчет</flux:button>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="xl">Месечна консумация на вода</flux:heading>
                </div>

                @if(count($chartData) > 1)
                <flux:chart wire:model="chartData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line curve="none" field="hot" class="text-red-500" name="Топла вода (m³)" />
                        <flux:chart.point field="hot" class="text-red-500" name="Топла вода (m³)" />
                        <flux:chart.line curve="none" field="cold" class="text-blue-500" name="Студена вода (m³)" />
                        <flux:chart.point field="cold" class="text-blue-500" name="Студена вода (m³)" />

                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.line />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :max="$maxValue">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor />
                    </flux:chart.svg>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" :format="['year' => 'numeric', 'month' => 'numeric', 'day' => 'numeric']" />
                        <flux:chart.tooltip.value field="hot" label="Топла вода" suffix=" m³" />
                        <flux:chart.tooltip.value field="cold" label="Студена вода" suffix=" m³" />
                    </flux:chart.tooltip>
                </flux:chart>
                @else
                <div class="flex flex-col items-center justify-center p-8 text-center">
                    <div class="rounded-full bg-blue-50 p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium">Няма данни за консумация на вода</p>
                    <p class="text-sm text-gray-500 mt-1">Моля, въведете показания на водомерите, за да видите статистика.</p>
                </div>
                @endif
            </flux:card>

            <!-- Water Loss Chart section -->
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="xl">Общо потребление и загуби</flux:heading>
                </div>

                @if(count($waterLossData) > 1)
                <flux:chart wire:model="waterLossData" class="aspect-3/1">
                    <flux:chart.svg>
                        <!-- Cold water metrics -->
                        <flux:chart.line curve="none" field="cold_water_total" class="text-blue-300" name="Общо студена вода (m³)" />
                        <flux:chart.point field="cold_water_total" class="text-blue-300" name="Общо студена вода (m³)" />
                        <flux:chart.line curve="none" field="cold_water_loss" class="text-blue-600" name="Загуби на студена вода (m³)" />
                        <flux:chart.point field="cold_water_loss" class="text-blue-600" name="Загуби на студена вода (m³)" />

                        <!-- Hot water metrics -->
                        <flux:chart.line curve="none" field="hot_water_total" class="text-red-300" name="Общо топла вода (m³)" />
                        <flux:chart.point field="hot_water_total" class="text-red-300" name="Общо топла вода (m³)" />
                        <flux:chart.line curve="none" field="hot_water_loss" class="text-red-600" name="Загуби на топла вода (m³)" />
                        <flux:chart.point field="hot_water_loss" class="text-red-600" name="Загуби на топла вода (m³)" />

                        <flux:chart.axis axis="x" field="date">
                            <flux:chart.axis.line />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.axis axis="y" :max="$maxLossValue">
                            <flux:chart.axis.grid />
                            <flux:chart.axis.tick />
                        </flux:chart.axis>

                        <flux:chart.cursor />
                    </flux:chart.svg>

                    <flux:chart.tooltip>
                        <flux:chart.tooltip.heading field="date" :format="['year' => 'numeric', 'month' => 'numeric', 'day' => 'numeric']" />
                        <flux:chart.tooltip.value field="cold_water_total" label="Общо студена вода" suffix=" m³" />
                        <flux:chart.tooltip.value field="cold_water_loss" label="Загуби на студена вода" suffix=" m³" />
                        <flux:chart.tooltip.value field="hot_water_total" label="Общо топла вода" suffix=" m³" />
                        <flux:chart.tooltip.value field="hot_water_loss" label="Загуби на топла вода" suffix=" m³" />
                    </flux:chart.tooltip>
                </flux:chart>
                @else
                <div class="flex flex-col items-center justify-center p-8 text-center">
                    <div class="rounded-full bg-blue-50 p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium">Няма данни за потребление и загуби</p>
                    <p class="text-sm text-gray-500 mt-1">За този анализ са необходими данни от централните водомери на сградата.</p>
                </div>
                @endif
            </flux:card>

            <!-- Apartment Readings Table Section -->
            <flux:card class="space-y-6 col-span-1 md:col-span-2">
                <div>
                    <flux:heading size="xl">Отчети по апартаменти</flux:heading>
                    <p class="text-sm text-gray-500 mt-1">Състояние на отчетите по месеци и апартаменти</p>
                </div>

                @if(count($readingsTableData['months']) > 0 && count($readingsTableData['apartments']) > 0)
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column pin="left">Апартамент</flux:table.column>
                            @foreach($readingsTableData['months'] as $month)
                            <flux:table.column class="text-center border-l border-gray-200">
                                <div class="w-full text-center">{{ Carbon\Carbon::parse($month['date'])->translatedFormat('F Y') }}</div>
                            </flux:table.column>
                            @endforeach
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($readingsTableData['apartments'] as $apartment)
                            <flux:table.row>
                                <flux:table.cell pin="left" variant="strong">
                                    Етаж {{ $apartment['floor'] }}, {{ $apartment['number'] }}
                                </flux:table.cell>
                                @foreach($apartment['readings'] as $reading)
                                <flux:table.cell class="text-center border-l border-gray-200">
                                    @if($reading['status'] === 'complete')
                                    <flux:badge color="green" size="sm" inset="top bottom">
                                        <span class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            Пълен
                                        </span>
                                    </flux:badge>
                                    @elseif($reading['status'] === 'partial')
                                    <flux:badge color="yellow" size="sm" inset="top bottom" variant="solid">
                                        <span class="flex items-center">
                                            <span class="text-xs font-medium">{{ $reading['submitted'] }}/{{ $reading['total'] }}</span>
                                        </span>
                                    </flux:badge>
                                    @else
                                    <flux:badge color="red" size="sm" inset="top bottom" variant="solid">
                                        <span class="flex items-center">
                                            <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512">
                                                <path d="M96 64c0-17.7-14.3-32-32-32S32 46.3 32 64l0 256c0 17.7 14.3 32 32 32s32-14.3 32-32L96 64zM64 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z" fill="currentColor" />
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
                </div>

                <div class="flex flex-wrap items-center gap-6 text-sm mt-4">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Всички показания са въведени</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-yellow-100 mr-2">
                            <span class="text-xs font-medium text-yellow-800">a/b</span>
                        </span>
                        <span>Частични показания (a от b водомера)</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full mr-2">
                            <svg class="h-4 w-4 fill-current text-red-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
                                <path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z" />
                            </svg>
                        </span>
                        <span>Няма показания</span>
                    </div>
                </div>
                @else
                <div class="flex flex-col items-center justify-center p-8 text-center">
                    <div class="rounded-full bg-blue-50 p-3 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium">Няма данни за отчети на апартаменти</p>
                    <p class="text-sm text-gray-500 mt-1">За да видите статистика, въведете показания на водомерите.</p>
                </div>
                @endif
            </flux:card>
        </div>

    </div>
</div>