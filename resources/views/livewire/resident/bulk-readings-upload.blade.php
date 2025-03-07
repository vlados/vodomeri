<div>
    <h1 class="text-2xl font-semibold mb-6">Автоматично разпознаване на показания</h1>
    
    @if (session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

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
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Ново!</strong> Качете снимки на водомерите си и системата ще разпознае автоматично показанията. Снимките може да съдържат един или повече водомери.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-medium mb-4">Стъпка 1: Изберете апартамент и качете снимки</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
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

            <div class="space-y-1 mb-6">
                <flux:label>Снимки на водомерите</flux:label>
                <flux:input
                    type="file"
                    wire:model="photos"
                    multiple
                    accept="image/*"
                    :error="$errors->has('photos') || $errors->has('photos.*')" />
                @error('photos')
                    <div class="text-xs text-red-600">{{ $message }}</div>
                @enderror
                @error('photos.*')
                    <div class="text-xs text-red-600">{{ $message }}</div>
                @enderror
                <div class="text-xs text-gray-500 mt-1">
                    Можете да качите една или няколко снимки. Всяка снимка може да съдържа един или повече водомери.
                </div>
            </div>

            <form wire:submit="processPhotos" class="flex">
                <flux:button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="processPhotos,photos"
                    variant="primary">
                    <span wire:loading.remove wire:target="processPhotos">Разпознай показанията</span>
                    <span wire:loading wire:target="processPhotos">
                        <svg class="animate-spin h-5 w-5 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Обработка...
                    </span>
                </flux:button>
            </form>
        </div>

        @if ($isProcessing)
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex flex-col items-center justify-center py-6">
                    <svg class="animate-spin h-10 w-10 text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900">Обработваме снимките...</h3>
                    <p class="text-sm text-gray-500 mt-2">Това може да отнеме около минута. Моля, изчакайте.</p>
                </div>
            </div>
        @endif

        @if ($processingComplete)
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium mb-4">Стъпка 2: Преглед и потвърждение на разпознатите показания</h2>
                
                @if (empty($recognizedMeters))
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Не успяхме да разпознаем водомери на снимките. Моля, опитайте с по-ясни снимки или въведете показанията ръчно.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    Разпознахме успешно {{ count($recognizedMeters) }} водомера. Моля, проверете показанията и коригирайте при нужда.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Тип</flux:table.column>
                        <flux:table.column>Водомер</flux:table.column>
                        <flux:table.column>Предишно показание</flux:table.column>
                        <flux:table.column>Ново показание</flux:table.column>
                        <flux:table.column>Статус</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($meters as $index => $meter)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:badge size="sm" class="uppercase" variant="solid" color="{{ $meter['type'] === 'hot' ? 'red' : 'blue' }}">{{ $meter['type'] === 'hot' ? 'Топла' : 'Студена' }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    <div class="text-gray-500">№: {{ $meter['serial_number'] }}</div>
                                    <div class="text-gray-500">Стая: {{ $meter['location'] ?: 'Не е посочено' }}</div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-medium">{{ number_format($meter['previous_value'], 3) }} m³</div>
                                <div class="text-gray-500">{{ $meter['previous_date'] }}</div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="space-y-1">
                                    <flux:input
                                        type="number"
                                        id="meter-value-{{ $index }}"
                                        wire:model="meters.{{ $index }}.value"
                                        step="1"
                                        placeholder="00000"
                                        suffix="m³"
                                        size="sm"
                                        :error="$errors->has('meters.' . $index . '.value')" />
                                    @error('meters.' . $index . '.value')
                                        <div class="text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($meter['is_recognized'])
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Разпознато</span>
                                    </div>
                                @else
                                    <div class="text-gray-500">Не е разпознато</div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="pt-4 flex space-x-4">
                    <flux:button wire:click="submit" variant="primary">
                        Запиши показанията
                    </flux:button>
                    
                    <flux:button wire:click="resetForm" variant="outline">
                        Отказ
                    </flux:button>
                </div>
            </div>
        @endif
    @endif
</div>