
            <div>
                <h1 class="text-2xl font-semibold mb-6">Добавяне на нов водомер</h1>
                
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
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <p class="text-sm text-gray-600">
                            Използвайте този формуляр, за да регистрирате нов водомер за вашия апартамент. След подаването, водомерът ще бъде прегледан от администратор.
                        </p>
                    </div>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:select
                                wire:model="selectedApartmentId"
                                label="Апартамент"
                                id="selectedApartmentId"
                            >
                                @foreach ($apartments as $apartment)
                                    <option value="{{ $apartment->id }}">Апартамент #{{ $apartment->number }} (Етаж {{ $apartment->floor }})</option>
                                @endforeach
                            </flux:select>
                            
                            <flux:select
                                wire:model="type"
                                label="Тип водомер"
                                id="type"
                            >
                                <option value="cold">Студена вода</option>
                                <option value="hot">Топла вода</option>
                            </flux:select>
                        </div>
                        
                        <flux:input
                            wire:model="serialNumber"
                            label="Сериен номер"
                            id="serialNumber"
                            placeholder="напр. СН12345678"
                        />
                        
                        <flux:input
                            wire:model="location"
                            label="Местоположение (по избор)"
                            id="location"
                            placeholder="напр. Баня, Кухня"
                        />
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input
                                wire:model="installationDate"
                                type="date"
                                label="Дата на инсталиране"
                                id="installationDate"
                                max="{{ now()->format('Y-m-d') }}"
                            />
                            
                            <flux:input
                                wire:model="initialReading"
                                type="number"
                                label="Начално показание"
                                id="initialReading"
                                step="0.001"
                                placeholder="000.000"
                                suffix="m³"
                            />
                        </div>
                        
                        <div class="pt-4 flex items-center justify-between">
                            <flux:link href="{{ route('dashboard') }}">
                                Отказ
                            </flux:link>
                            
                            <flux:button type="submit" variant="primary">
                                Добави водомер
                            </flux:button>
                        </div>
                    </form>
                @endif