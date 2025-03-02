<div>
    <div class="grid grid-cols-1 gap-y-6 ">
        <!-- Header with actions -->
        <div class="flex justify-between items-center">

            <flux:spacer />
            <div>
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
        <div class="grid grid-cols-1 gap-x-10 md:grid-cols-2">
            <flux:card class="shadow-md border-0">
                <div class="">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-medium">Месечна консумация на вода</h2>
                    </div>

                    <flux:chart wire:model="chartData" class="aspect-3/1 h-full">
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
                </div>
            </flux:card>

            <!-- Water Loss Chart section -->
            <flux:card class="shadow-md border-0">
                <div class="">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-medium">Загуби на вода (Централен водомер - Апартаменти)</h2>
                    </div>

                    <flux:chart wire:model="waterLossData" class="aspect-3/1 h-full">
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
                </div>
        </div>
        </flux:card>

        <!-- Chart section -->

    </div>
</div>