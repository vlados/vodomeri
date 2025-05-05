<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <h1 class="text-2xl font-semibold">Автоматично разпознаване на показания</h1>
        <div class="mt-2 sm:mt-0 text-gray-500 font-medium flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Показания за: {{ \Carbon\Carbon::parse($readingDate)->format('d.m.Y') }}</span>
        </div>
    </div>
    
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

            <div class="space-y-3 mb-6">
                <flux:label>Снимки на водомерите</flux:label>
                
                <div 
                    x-data="{ 
                        isHovering: false,
                        isUploading: false,
                        progress: 0,
                        handleDragOver(e) { e.preventDefault(); this.isHovering = true; },
                        handleDragLeave(e) { e.preventDefault(); this.isHovering = false; },
                        handleDrop(e) {
                            e.preventDefault();
                            this.isHovering = false;
                            
                            if (e.dataTransfer.files.length) {
                                const fileInput = document.getElementById('photo-upload');
                                fileInput.files = e.dataTransfer.files;
                                
                                // Trigger Livewire file upload
                                const event = new Event('change', { bubbles: true });
                                fileInput.dispatchEvent(event);
                            }
                        }
                    }"
                    x-on:dragover="handleDragOver($event)"
                    x-on:dragleave="handleDragLeave($event)"
                    x-on:drop="handleDrop($event)"
                    x-on:livewire-upload-start="isUploading = true"
                    x-on:livewire-upload-finish="isUploading = false; progress = 0"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    :class="{ 'bg-blue-50 border-blue-300': isHovering }"
                    class="border-2 border-dashed rounded-lg p-6 transition-colors flex flex-col items-center justify-center">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    
                    <div class="text-center mb-2">
                        <p class="font-medium text-gray-900">Влачете и пуснете снимки тук</p>
                        <p class="text-sm text-gray-500">или</p>
                    </div>
                    
                    <label for="photo-upload" class="cursor-pointer bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                        Изберете снимки от устройството
                    </label>
                    
                    <input
                        id="photo-upload"
                        type="file"
                        class="hidden"
                        wire:model="photos"
                        multiple
                        accept="image/*" />
                        
                    <div x-show="isUploading" class="w-full mt-4">
                        <div class="bg-gray-200 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${progress}%`"></div>
                        </div>
                        <p class="text-sm text-gray-500 text-center mt-1" x-text="`Качване... ${progress}%`"></p>
                    </div>
                </div>
                
                @error('photos')
                    <div class="text-xs text-red-600">{{ $message }}</div>
                @enderror
                @error('photos.*')
                    <div class="text-xs text-red-600">{{ $message }}</div>
                @enderror
                
                <div class="text-xs text-gray-500">
                    <p>Можете да качите една или няколко снимки. Всяка снимка може да съдържа един или повече водомери.</p>
                    <p class="mt-1">Поддържани формати: JPG, PNG, GIF. Максимален размер: 5MB на файл.</p>
                </div>
                
                @if(!empty($photos))
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Избрани снимки: {{ count($photos) }}</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach($photos as $index => $photo)
                        <div class="relative">
                            <img src="{{ $photo->temporaryUrl() }}" class="h-24 w-full object-cover rounded border" />
                            <button type="button" wire:click="removePhoto({{ $index }})" class="absolute top-1 right-1 bg-red-100 text-red-600 rounded-full p-1 hover:bg-red-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
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
                
                @if (!empty($foreignMeters))
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 font-medium">
                                    Внимание! Открихме водомери, които не са част от избрания апартамент.
                                </p>
                                <p class="text-sm text-red-700 mt-1">
                                    Снимките трябва да съдържат само водомери от апартамент {{ $apartments->firstWhere('id', $selectedApartmentId)->number ?? '' }}. 
                                    Моля, проверете и качете отново само снимки на водомерите от този апартамент.
                                </p>
                                
                                <div class="mt-2 space-y-2">
                                    @foreach($foreignMeters as $meter)
                                        <div class="flex items-center text-sm text-red-700">
                                            <span class="font-medium mr-2">•</span>
                                            <span>Открит чужд водомер с №: {{ $meter['serial_number'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
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
                                        :error="$errors->has('meters.' . $index . '.value') || isset($meter['value_warning'])" />
                                    @error('meters.' . $index . '.value')
                                        <div class="text-xs text-red-600">{{ $message }}</div>
                                    @enderror
                                    
                                    @if(isset($meter['value_warning']))
                                        <div class="text-xs text-amber-600">
                                            {{ $meter['value_warning'] }}
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if($meter['is_recognized'])
                                    <div class="flex flex-col">
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 text-green-500 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <span>Разпознато</span>
                                        </div>
                                        
                                        @if(isset($meter['confidence']))
                                            <div class="mt-1 text-sm">
                                                <span class="text-gray-600">Сигурност:</span>
                                                <span class="{{ $meter['confidence'] === 'high' ? 'text-green-600' : ($meter['confidence'] === 'medium' ? 'text-amber-600' : 'text-red-600') }}">
                                                    {{ $meter['confidence'] === 'high' ? 'Висока' : ($meter['confidence'] === 'medium' ? 'Средна' : 'Ниска') }}
                                                </span>
                                            </div>
                                        @endif
                                        
                                        @if(isset($meter['photo_path']) && $meter['photo_path'])
                                            <a href="{{ Storage::url($meter['photo_path']) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm mt-1">
                                                Преглед на снимката
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex flex-col">
                                        <div class="text-gray-500">Не е разпознато</div>
                                        <flux:button wire:click="addMeterManually({{ $index }})" size="xs" variant="outline" class="mt-2">
                                            Добави ръчно
                                        </flux:button>
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-medium text-blue-900 mb-2">Преглед на консумация</h3>
                    
                    <div class="space-y-4">
                        @foreach($meters as $index => $meter)
                            @if($meter['is_recognized'] && !empty($meter['value']))
                                @php
                                    $previousValue = (float) $meter['previous_value'];
                                    $newValue = (float) $meter['value'];
                                    $consumption = $newValue - $previousValue;
                                    $isWarning = $consumption <= 0;
                                @endphp
                                
                                <div class="flex flex-col sm:flex-row justify-between p-3 {{ $isWarning ? 'bg-amber-50' : 'bg-white' }} border rounded-lg">
                                    <div>
                                        <div class="flex items-center">
                                            <flux:badge size="sm" class="uppercase mr-2" variant="solid" color="{{ $meter['type'] === 'hot' ? 'red' : 'blue' }}">
                                                {{ $meter['type'] === 'hot' ? 'Топла' : 'Студена' }}
                                            </flux:badge>
                                            <span class="font-medium">{{ $meter['location'] ?: 'Водомер' }} ({{ $meter['serial_number'] }})</span>
                                        </div>
                                        
                                        <div class="text-sm text-gray-500 mt-1">
                                            <span>Предишно показание: {{ number_format($meter['previous_value'], 3) }} m³</span>
                                            <span class="mx-2">→</span>
                                            <span>Ново показание: {{ number_format($meter['value'], 3) }} m³</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 sm:mt-0">
                                        <div class="flex items-center">
                                            <span class="font-semibold text-lg {{ $isWarning ? 'text-amber-600' : 'text-green-600' }}">
                                                {{ number_format($consumption, 3) }} m³
                                            </span>
                                            <span class="text-sm ml-1 {{ $isWarning ? 'text-amber-600' : 'text-gray-500' }}">
                                                консумация
                                            </span>
                                        </div>
                                        
                                        @if($isWarning)
                                            <div class="text-amber-600 text-xs mt-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                Консумацията е нулева или отрицателна, моля проверете показанието.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                
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