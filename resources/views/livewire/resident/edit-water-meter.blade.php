
            <div>
                <h1 class="text-2xl font-semibold mb-6">Редактиране на водомер</h1>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <p class="text-sm text-gray-600">
                        Редактирайте данните за вашия водомер. След запазване, промените ще бъдат прегледани от администратор.
                    </p>
                </div>
                
                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-1">Апартамент</p>
                            <p class="text-gray-900">Апартамент #{{ $waterMeter->apartment->number }} (Етаж {{ $waterMeter->apartment->floor }})</p>
                        </div>
                        
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
                            Запази промените
                        </flux:button>
                    </div>
                </form>