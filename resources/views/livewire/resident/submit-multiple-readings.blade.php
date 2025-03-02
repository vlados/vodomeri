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
    <form wire:submit="verifyReadings" class="space-y-6">
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
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Важно:</strong> За първо отчитане на водомер е задължително да прикачите снимка на показанието. За следващи отчитания снимката е препоръчителна, но не е задължителна.
                    </p>
                </div>
            </div>
        </div>

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
                            <div class="flex items-center gap-2">
                                @if($meter['previous_date'] === 'Initial')
                                <span class="text-red-500 text-xs font-medium">* Задължително</span>
                                @endif
                                <flux:input
                                    type="file"
                                    id="meter-photo-{{ $index }}"
                                    wire:model="meters.{{ $index }}.photo"
                                    accept="image/*"
                                    size="sm"
                                    :error="$errors->has('meters.' . $index . '.photo')" />
                            </div>
                            @error('meters.' . $index . '.photo')
                                <div class="text-xs text-red-600">{{ $message }}</div>
                            @enderror
                            @if($meter['previous_date'] === 'Initial')
                                <div class="text-xs text-gray-500">Снимката е задължителна за първо отчитане</div>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        @endif

        <div class="pt-4 flex space-x-4">
            <flux:button type="submit" variant="primary">
                Провери и изпрати показания
            </flux:button>
            
            <flux:button wire:click="skipVerification"  type="button">
                Изпрати без AI проверка
            </flux:button>
        </div>
    </form>
    
    @if (count($verificationResults) > 0)
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Резултати от AI верификация</h2>
        
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Водомер</flux:table.column>
                <flux:table.column>Статус</flux:table.column>
                <flux:table.column>Детайли</flux:table.column>
                <flux:table.column>Действие</flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @foreach ($meters as $index => $meter)
                @if (isset($verificationResults[$index]))
                <flux:table.row>
                    <flux:table.cell>
                        <div>
                            <flux:badge size="sm" class="uppercase" variant="solid" color="{{ $meter['type'] === 'hot' ? 'red' : 'blue' }}">{{ $meter['type'] === 'hot' ? 'Топла' : 'Студена' }}</flux:badge>
                            <div class="mt-1">№: {{ $meter['serial_number'] }}</div>
                            <div class=" text-gray-500">Показание: {{ $meter['value'] ? number_format($meter['value'], 3) : 'Не е посочено' }} m³</div>
                        </div>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @if ($verificationResults[$index]['status'] === 'success')
                            <flux:badge color="green" variant="outline">Потвърдено</flux:badge>
                        @elseif ($verificationResults[$index]['status'] === 'error')
                            <flux:badge color="red" variant="outline">Проблем</flux:badge>
                        @else
                            <flux:badge color="gray" variant="outline">Пропуснато</flux:badge>
                        @endif
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        <div class="text-sm">
                            {{ $verificationResults[$index]['message'] }}
                            
                            @if (isset($verificationResults[$index]['details']['extracted']))
                            <div class="mt-1 text-gray-600">
                                @if (isset($verificationResults[$index]['details']['extracted']['serial_number']))
                                    <div>Открит сериен номер: {{ $verificationResults[$index]['details']['extracted']['serial_number'] }}</div>
                                @endif
                                
                                @if (isset($verificationResults[$index]['details']['extracted']['reading']))
                                    <div>Открито показание: {{ $verificationResults[$index]['details']['extracted']['reading'] }}</div>
                                @endif
                            </div>
                            @endif
                            
                            @if (isset($verificationResults[$index]['details']['issues']) && $verificationResults[$index]['details']['issues'])
                            <div class="mt-1 text-amber-600">
                                Проблеми: {{ $verificationResults[$index]['details']['issues'] }}
                            </div>
                            @endif
                        </div>
                    </flux:table.cell>
                    
                    <flux:table.cell>
                        @if ($verificationResults[$index]['status'] === 'error')
                        <div class="space-y-2">
                            <div><flux:button size="xs" wire:click="submit" variant="outline">Изпрати въпреки това</flux:button></div>
                        </div>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
                @endif
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
    @endif
    @endif