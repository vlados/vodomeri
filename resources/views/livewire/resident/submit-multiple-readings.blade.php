<div>
    <h1 class="text-2xl font-semibold mb-6">Подаване на самоотчет</h1>

    @if ($apartments->isEmpty())
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    Нямате апартаменти, свързани с вашия акаунт. Моля, свържете се с администратор.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
            &larr; Обратно към началната страница
        </a>
    </div>
    @else
    <form wire:submit="submit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:select
                wire:model.live="selectedApartmentId"
                label="Изберете апартамент"
                id="selectedApartmentId">
                @foreach ($apartments as $apartment)
                <option value="{{ $apartment->id }}">Апартамент #{{ $apartment->number }} (Етаж {{ $apartment->floor }})</option>
                @endforeach
            </flux:select>

            <div class="space-y-1">
                <flux:date-picker
                    wire:model="readingDate"
                    label="Дата на отчитане"
                    with-today
                    :error="$errors->has('readingDate')" />
                @error('readingDate')
                    <div class="text-xs text-red-600">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if (empty($meters))
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <p class="text-sm text-yellow-700">
                Не са намерени водомери за този апартамент.
            </p>
        </div>
        @else

        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable>Тип</flux:table.column>
                <flux:table.column sortable>Водомер</flux:table.column>
                <flux:table.column>Предишно показание</flux:table.column>
                <flux:table.column>Ново показание</flux:table.column>
                <flux:table.column>Снимка</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($meters as $index => $meter)
                <flux:table.row>
                    <flux:table.cell>
                        <flux:badge size="sm" class="uppercase" variant="solid" color="{{ $meter['type'] === 'hot' ? 'red' : 'blue' }}">{{ $meter['type'] === 'hot' ? 'Топла' : 'Студена' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div>
                            <div class=" text-gray-500">№: {{ $meter['serial_number'] }}</div>
                            <div class=" text-gray-500">Стая: {{ $meter['location'] ?: 'Не е посочено' }}</div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="font-medium">{{ number_format($meter['previous_value'], 3) }} m³</div>
                        <div class=" text-gray-500">{{ $meter['previous_date'] }}</div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="space-y-1">
                            <flux:input
                                type="number"
                                id="meter-value-{{ $index }}"
                                wire:model="meters.{{ $index }}.value"
                                step="0.001"
                                placeholder="000.000"
                                suffix="m³"
                                size="sm"
                                :error="$errors->has('meters.' . $index . '.value')" />
                            @error('meters.' . $index . '.value')
                                <div class="text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="space-y-1">
                            <flux:input
                                type="file"
                                id="meter-photo-{{ $index }}"
                                wire:model="meters.{{ $index }}.photo"
                                accept="image/*"
                                size="sm"
                                :error="$errors->has('meters.' . $index . '.photo')" />
                            @error('meters.' . $index . '.photo')
                                <div class="text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        @endif

        <div class="pt-4 flex">
            <flux:button type="submit" variant="primary">
                Изпрати всички показания
            </flux:button>
        </div>
    </form>
    @endif