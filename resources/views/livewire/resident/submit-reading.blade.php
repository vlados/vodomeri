
            <div>
                @if (!$waterMeter)
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">
                                    Водомерът не е намерен или нямате достъп до него.
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
                    <h1 class="text-2xl font-semibold mb-6">Въвеждане на ново показание</h1>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h2 class="text-lg font-medium mb-2">
                            Водомер за {{ $waterMeter->type === 'hot' ? 'топла' : 'студена' }} вода (Апартамент #{{ $waterMeter->apartment->number }})
                        </h2>
                        <p class="text-sm text-gray-600">Сериен номер: {{ $waterMeter->serial_number }}</p>
                        <p class="text-sm text-gray-600">Местоположение: {{ $waterMeter->location ?: 'Не е посочено' }}</p>
                        
                        @if ($previousReading)
                            <div class="mt-4 p-3 bg-white rounded border">
                                <p class="text-sm font-medium">Предишно показание:</p>
                                <p class="text-lg font-semibold">{{ number_format($previousReading->value, 3) }} m³</p>
                                <p class="text-xs text-gray-500">на {{ $previousReading->reading_date->format('d.m.Y') }}</p>
                            </div>
                        @else
                            <div class="mt-4 p-3 bg-white rounded border">
                                <p class="text-sm font-medium">Начално показание:</p>
                                <p class="text-lg font-semibold">{{ number_format($initialReading, 3) }} m³</p>
                                <p class="text-xs text-gray-500">при инсталиране</p>
                            </div>
                        @endif
                    </div>
                    
                    <form wire:submit="submit" class="space-y-4">
                        <div>
                            <label for="readingDate" class="block text-sm font-medium text-gray-700">Дата на отчитане</label>
                            <input type="date" id="readingDate" wire:model="readingDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" max="{{ now()->format('Y-m-d') }}">
                            @error('readingDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label for="value" class="block text-sm font-medium text-gray-700">Показание (m³)</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="number" id="value" wire:model="value" step="0.001" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="000.000">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">m³</span>
                                </div>
                            </div>
                            @error('value') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label for="photo" class="block text-sm font-medium text-gray-700">Снимка на водомера (по избор)</label>
                            <input type="file" id="photo" wire:model="photo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" accept="image/*">
                            <p class="mt-1 text-sm text-gray-500">Направете ясна снимка на дисплея на водомера, показваща текущото показание.</p>
                            @error('photo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">Бележки (по избор)</label>
                            <textarea id="notes" wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Допълнителна информация относно това отчитане..."></textarea>
                            @error('notes') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="pt-4 flex items-center justify-between">                            
                            <flux:button type="submit" variant="primary">
                                Изпрати отчитане
                            </flux:button>
                        </div>
                    </form>
                @endif

