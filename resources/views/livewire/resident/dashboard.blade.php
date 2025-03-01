
            <div>
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold">Моите водомери</h1>
                    <div class="flex space-x-2">
                        <a href="{{ route('readings.multiple') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Изпрати всички показания
                        </a>
                        <a href="{{ route('meters.add') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Добави водомер
                        </a>
                    </div>
                </div>
                
                
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
                @else
                    @foreach ($apartments as $apartment)
                        <div class="mb-8 bg-gray-50 p-6 rounded-lg shadow-sm">
                            <h2 class="text-xl font-medium mb-4">Апартамент #{{ $apartment->number }} (Етаж {{ $apartment->floor }})</h2>
                            
                            @if (!isset($metersByApartment[$apartment->id]) || $metersByApartment[$apartment->id]->isEmpty())
                                <p class="text-gray-500 italic">Няма регистрирани водомери за този апартамент.</p>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach ($metersByApartment[$apartment->id] as $meter)
                                        <div class="bg-white p-4 rounded-md border {{ $meter->type === 'hot' ? 'border-red-200' : 'border-blue-200' }}">
                                            <div class="flex justify-between items-center mb-3">
                                                <h3 class="font-medium {{ $meter->type === 'hot' ? 'text-red-600' : 'text-blue-600' }}">
                                                    {{ $meter->type === 'hot' ? 'Топла' : 'Студена' }} вода
                                                </h3>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $meter->serial_number }}</span>
                                                    <a href="{{ route('meters.edit', ['meterId' => $meter->id]) }}" class="text-gray-500 hover:text-gray-700" title="Редактирай">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="text-xs text-gray-500">Местоположение: {{ $meter->location ?: 'Не е посочено' }}</p>
                                                <p class="text-xs text-gray-500">Инсталиран на: {{ $meter->installation_date ? $meter->installation_date->format('d.m.Y') : 'Неизвестно' }}</p>
                                            </div>
                                            
                                            <div class="border-t border-gray-100 pt-3">
                                                <div class="flex justify-between">
                                                    <div>
                                                        <p class="text-xs text-gray-500">Последно отчитане:</p>
                                                        @if ($meter->latestReading)
                                                            <p class="font-semibold">{{ number_format($meter->latestReading->value, 3) }} m³</p>
                                                            <p class="text-xs text-gray-500">{{ $meter->latestReading->reading_date->format('d.m.Y') }}</p>
                                                        @else
                                                            <p class="text-sm text-gray-400 italic">Все още няма отчитания</p>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <a href="{{ route('readings.submit', ['meterId' => $meter->id]) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-white {{ $meter->type === 'hot' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                                                            Въведи показание
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
                
                <div class="mt-6">
                    <h3 class="text-lg font-medium mb-2">Последна активност</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <a href="{{ route('readings.history') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            История на отчитанията →
                        </a>
                    </div>
                </div>

