<div>
    <div class="grid grid-cols-1 gap-10">
        <!-- Header with actions -->
        <div class="flex justify-between items-center w-full">
            <div class="flex space-x-2">
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

            <div class="flex space-x-2 ml-2">
                <flux:button href="{{ route('meters.add') }}" icon="plus" size="sm">Нов водомер</flux:button>
                <flux:button href="{{ route('readings.multiple') }}" icon="pencil-square" size="sm" variant="primary">Самоотчет</flux:button>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2">
            <flux:card class=" space-y-6">
                <div>
                    <flux:heading size="xl">Месечна консумация на вода</flux:heading>
                </div>

                <flux:chart wire:model="chartData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line field="hot" class="text-red-500" name="Топла вода (m³)" />
                        <flux:chart.line field="cold" class="text-blue-500" name="Студена вода (m³)" />

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
            </flux:card>

            <!-- Water Loss Chart section -->
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="xl">Загуби на вода</flux:heading>
                </div>

                <flux:chart wire:model="waterLossData" class="aspect-3/1">
                    <flux:chart.svg>
                        <flux:chart.line field="water_loss" class="text-purple-500" name="Загуби на вода (m³)" />

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
                        <flux:chart.tooltip.value field="water_loss" label="Загуби на вода" suffix=" m³" />
                    </flux:chart.tooltip>
                </flux:chart>
            </flux:card>

            <!-- Apartment Readings Table Section -->
            <flux:card class="space-y-6 col-span-1 md:col-span-2">
                <div>
                    <flux:heading size="xl">Отчети по апартаменти</flux:heading>
                    <p class="text-sm text-gray-500 mt-1">Състояние на отчетите по месеци и апартаменти</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50">
                                    Апартамент
                                </th>
                                @foreach($readingsTableData['months'] as $month)
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ Carbon\Carbon::parse($month['date'])->format('M Y') }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($readingsTableData['apartments'] as $apartment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                    Етаж {{ $apartment['floor'] }}, Ап. {{ $apartment['number'] }}
                                </td>
                                @foreach($apartment['readings'] as $reading)
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                    @if($reading['status'] === 'complete')
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    @elseif($reading['status'] === 'partial')
                                    <flux:tooltip content="Подадени са само {{ $reading['submitted'] }} показания от {{ $reading['total'] }}">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100">
                                        <span class="text-xs font-medium text-yellow-800">{{ $reading['submitted'] }}/{{ $reading['total'] }}</span>
                                    </span>
                                    </flux:tooltip>
                                    @else
                                    <flux:tooltip content="Няма въведени показания">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-600">
                                        <svg class="h-5 w-5 fill-current text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M96 64c0-17.7-14.3-32-32-32S32 46.3 32 64l0 256c0 17.7 14.3 32 32 32s32-14.3 32-32L96 64zM64 480a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>
                                    </span>
                                    </flux:tooltip>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center space-x-6 text-sm mt-4">
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
                        <svg class="h-4 w-4 fill-current text-red-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480L40 480c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24l0 112c0 13.3 10.7 24 24 24s24-10.7 24-24l0-112c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
                        </span>
                        <span>Няма показания</span>
                    </div>
                </div>
            </flux:card>
        </div>

    </div>
</div>