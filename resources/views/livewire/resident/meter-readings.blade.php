<div>
    <h1 class="text-2xl font-semibold mb-6">История на показанията</h1>
    
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select 
                id="meterType" 
                wire:model.live="filter.meter_type"
                label="Тип водомер">
                <option value="">Всички водомери</option>
                <option value="hot">Топла вода</option>
                <option value="cold">Студена вода</option>
            </flux:select>
            
            <flux:select 
                id="dateRange" 
                wire:model.live="filter.date_range"
                label="Предефиниран период"
                class="{{ !empty($filter['reading_date']) && $filter['reading_date'] !== 'all' ? 'opacity-50 cursor-not-allowed' : '' }}"
                :disabled="!empty($filter['reading_date'])">
                <option value="all">Цялото време</option>
                <option value="this_month">Този месец</option>
                <option value="last_month">Миналия месец</option>
                <option value="last_3_months">Последните 3 месеца</option>
                <option value="last_6_months">Последните 6 месеца</option>
            </flux:select>
            
            <flux:select 
                id="readingDate" 
                wire:model.live="filter.reading_date"
                label="Дата на отчитане"
                class="{{ !empty($filter['date_range']) && $filter['date_range'] !== 'all' ? 'opacity-50 cursor-not-allowed' : '' }}"
                :disabled="!empty($filter['date_range']) && $filter['date_range'] !== 'all'">
                <option value="">Всички дати</option>
                @foreach($availableDates as $date)
                    <option value="{{ $date['value'] }}">{{ $date['label'] }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>
                
    @if ($readings->isEmpty())
        <div>Няма намерени показания</div>
    @else
        <flux:table :paginate="$readings">
            <flux:table.columns>
                <flux:table.column wire:click="sortBy('serial_number')" sortable :sort-direction="$sortField === 'serial_number' ? $sortDirection : null">
                    Сериен номер
                </flux:table.column>
                <flux:table.column wire:click="sortBy('meter_type')" sortable :sort-direction="$sortField === 'meter_type' ? $sortDirection : null">
                    Тип водомер
                </flux:table.column>
                <flux:table.column wire:click="sortBy('reading_date')" sortable :sort-direction="$sortField === 'reading_date' ? $sortDirection : null">
                    Дата на отчитане
                </flux:table.column>
                <flux:table.column wire:click="sortBy('value')" sortable :sort-direction="$sortField === 'value' ? $sortDirection : null">
                    Показание
                </flux:table.column>
                <flux:table.column wire:click="sortBy('consumption')" sortable :sort-direction="$sortField === 'consumption' ? $sortDirection : null">
                    Консумация
                </flux:table.column>
            </flux:table.columns>
            
            <flux:table.rows>
                @foreach ($readings as $reading)
                    <flux:table.row>
                        <flux:table.cell>
                            {{ $reading->waterMeter->serial_number }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <flux:badge size="sm" class="uppercase" variant="solid" color="{{ $reading->waterMeter->type === 'hot' ? 'red' : 'blue' }}">
                                {{ $reading->waterMeter->type === 'hot' ? 'Топла' : 'Студена' }}
                            </flux:badge>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $reading->reading_date->format('d.m.Y') }}
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ number_format($reading->value, 3) }} m³
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            {{ $reading->consumption ? number_format($reading->consumption, 3) . ' m³' : '-' }}
                        </flux:table.cell>
                                                
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
