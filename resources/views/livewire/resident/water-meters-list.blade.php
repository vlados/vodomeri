<div>
    <!-- Responsive header with grid layout -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6 items-center">
        <h1 class="text-2xl font-semibold">Списък с водомери</h1>
        <div class="flex justify-start sm:justify-end">
            <flux:button href="{{ route('meters.add') }}" variant="primary" icon="plus-circle" class="w-full sm:w-auto justify-center">
                Добави водомер
            </flux:button>
        </div>
    </div>

    <!-- Responsive search and filter -->
    <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
            <flux:input
                wire:model.live="search"
                placeholder="Търсене..." 
                class="w-full" />
        </div>
        <div>
            <flux:select wire:model.live="typeFilter" class="w-full">
                <option value="">Всички типове</option>
                <option value="cold">Студена вода</option>
                <option value="hot">Топла вода</option>
            </flux:select>
        </div>
    </div>

    <!-- Desktop table view -->
    <div class="hidden md:block">
        <flux:table :paginate="$waterMeters">
            <flux:table.columns>
                <flux:table.column>Тип</flux:table.column>
                <flux:table.column>Сериен номер</flux:table.column>
                <flux:table.column>Местоположение</flux:table.column>
                <flux:table.column>Апартамент</flux:table.column>
                <flux:table.column>Дата на инсталиране</flux:table.column>
                <flux:table.column>Последно показание</flux:table.column>
                <flux:table.column>Действия</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($waterMeters as $meter)
                <flux:table.row :key="$meter->id">
                    <flux:table.cell>
                    <flux:badge size="sm" class="uppercase" variant="solid" color="{{ $meter['type'] === 'hot' ? 'red' : 'blue' }}">{{ $meter['type'] === 'hot' ? 'Топла' : 'Студена' }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>{{ $meter->serial_number }}</flux:table.cell>

                    <flux:table.cell>{{ $meter->location ?: 'Не е посочено' }}</flux:table.cell>

                    <flux:table.cell>Апартамент #{{ $meter->apartment->number }} (Етаж {{ $meter->apartment->floor }})</flux:table.cell>

                    <flux:table.cell>{{ $meter->installation_date ? $meter->installation_date->format('d.m.Y') : 'Неизвестно' }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($meter->latestReading)
                        <div>
                            <span class="font-semibold">{{ number_format($meter->latestReading->value, 3) }} m³</span>
                            <div class="text-xs text-gray-500">{{ $meter->latestReading->reading_date->format('d.m.Y') }}</div>
                        </div>
                        @else
                        <span class="text-gray-400 italic">Няма отчитания</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:button href="{{ route('meters.edit', ['meterId' => $meter->id]) }}" icon="pencil" size="sm">
                            <span>Редактирай</span>
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center text-gray-500">
                        Няма намерени водомери по зададените критерии.
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <!-- Mobile card view -->
    <div class="md:hidden space-y-4">
        @forelse ($waterMeters as $meter)
            <flux:card class="p-4">
                <div class="flex justify-between items-center mb-2">
                    <flux:badge size="sm" class="uppercase" variant="solid" color="{{ $meter['type'] === 'hot' ? 'red' : 'blue' }}">{{ $meter['type'] === 'hot' ? 'Топла' : 'Студена' }}</flux:badge>
                    <div class="text-sm text-gray-500">{{ $meter->installation_date ? $meter->installation_date->format('d.m.Y') : 'Неизвестно' }}</div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <div class="text-sm font-medium text-gray-500">Сериен номер:</div>
                        <div class="font-medium">{{ $meter->serial_number }}</div>
                    </div>
                    
                    <div class="flex justify-between">
                        <div class="text-sm font-medium text-gray-500">Местоположение:</div>
                        <div>{{ $meter->location ?: 'Не е посочено' }}</div>
                    </div>
                    
                    <div class="flex justify-between">
                        <div class="text-sm font-medium text-gray-500">Апартамент:</div>
                        <div>Апартамент #{{ $meter->apartment->number }} (Етаж {{ $meter->apartment->floor }})</div>
                    </div>
                    
                    <div class="flex justify-between">
                        <div class="text-sm font-medium text-gray-500">Последно показание:</div>
                        <div>
                            @if ($meter->latestReading)
                                <span class="font-semibold">{{ number_format($meter->latestReading->value, 3) }} m³</span>
                                <div class="text-xs text-gray-500">{{ $meter->latestReading->reading_date->format('d.m.Y') }}</div>
                            @else
                                <span class="text-gray-400 italic">Няма отчитания</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 flex justify-end">
                    <flux:button href="{{ route('meters.edit', ['meterId' => $meter->id]) }}" icon="pencil" size="sm" class="w-full justify-center">
                        Редактирай
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <flux:card class="p-4 text-center text-gray-500">
                Няма намерени водомери по зададените критерии.
            </flux:card>
        @endforelse
        
        <!-- Pagination for mobile -->
        <div class="mt-4">
            {{ $waterMeters->links() }}
        </div>
    </div>
</div>