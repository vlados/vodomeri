<div>
    @if (session('success'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-water-meter" wire:model="showDeleteModal" class="min-w-[28rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Изтриване на водомер</flux:heading>
                
                @if ($deleteErrorMessage)
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    {{ $deleteErrorMessage }}
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif ($readingsCount > 0)
                    <flux:subheading>
                        <p>Този водомер има <strong>{{ $readingsCount }}</strong> записани показания.</p>
                        <p>Ако продължите, <strong>всички показания</strong> ще бъдат изтрити заедно с водомера.</p>
                        <p>Това действие <strong>не може да бъде отменено</strong>.</p>
                    </flux:subheading>
                    
                    <div class="mt-4 p-3 border border-red-300 bg-red-50 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-800 font-medium">
                                    Предупреждение: Изтриването на водомер с показания може да доведе до загуба на важна информация за отчитане.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <flux:subheading>
                        <p>Сигурни ли сте, че искате да изтриете този водомер?</p>
                        <p>Това действие <strong>не може да бъде отменено</strong>.</p>
                    </flux:subheading>
                @endif
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                
                <flux:button wire:click="cancelDelete" variant="ghost">Отказ</flux:button>
                <flux:button wire:click="deleteWaterMeter" variant="danger">
                    @if ($readingsCount > 0)
                        Изтрий водомера и показанията
                    @else
                        Изтрий водомера
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>

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
                        <div class="flex space-x-2">
                            <flux:button href="{{ route('meters.edit', ['meterId' => $meter->id]) }}" icon="pencil" size="sm">
                                <span>Редактирай</span>
                            </flux:button>
                            
                            <flux:button 
                                wire:click="confirmDelete({{ $meter->id }})" 
                                icon="trash" 
                                size="sm"
                                variant="danger">
                                <span>Изтрий</span>
                            </flux:button>
                        </div>
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
                
                <div class="mt-4 flex justify-end space-x-2">
                    <flux:button href="{{ route('meters.edit', ['meterId' => $meter->id]) }}" icon="pencil" size="sm" class="w-full justify-center">
                        Редактирай
                    </flux:button>
                    
                    <flux:button 
                        wire:click="confirmDelete({{ $meter->id }})" 
                        icon="trash" 
                        size="sm"
                        variant="danger"
                        class="w-full justify-center">
                        Изтрий
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