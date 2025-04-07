<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Справки за консумация на вода') }}</h2>
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('Генерирайте месечни справки за консумацията на вода във вашата сграда') }}</p>
            </div>
        </div>

        <!-- Report Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Hot Water Report Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition duration-300">
                <div class="p-6 flex flex-col h-full">
                    <div class="flex items-center mb-4">
                        <div class="rounded-full bg-red-100 dark:bg-red-900 p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 dark:text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Гореща вода') }}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('Справка за месечна консумация на гореща вода по апартаменти и собственици, включваща стари и нови показания.') }}</p>
                    <div class="mt-auto">
                        <button
                            onclick="Livewire.dispatch('open-modal', { id: 'report-generator', data: { type: 'hot_water' }})"
                            class="inline-flex items-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition duration-300"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            {{ __('Генерирай справка') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cold Water Report Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition duration-300">
                <div class="p-6 flex flex-col h-full">
                    <div class="flex items-center mb-4">
                        <div class="rounded-full bg-blue-100 dark:bg-blue-900 p-3 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Студена вода') }}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('Справка за месечна консумация на студена вода по апартаменти и собственици, включваща стари и нови показания.') }}</p>
                    <div class="mt-auto">
                        <button
                            onclick="Livewire.dispatch('open-modal', { id: 'report-generator', data: { type: 'cold_water' }})"
                            class="inline-flex items-center px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition duration-300"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            {{ __('Генерирай справка') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">{{ __('Характеристики на справките') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Справките са подредени по етажи и номера на апартаментите') }}</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Включват информация за собственика и номера на апартамента') }}</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Съдържат сериен номер на всеки водомер') }}</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Показват стари и нови показания, както и изчислена консумация') }}</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Поддръжка на експорт в Excel формат') }}</p>
                </div>
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('Форматирани клетки с подравняване и стилове') }}</p>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-md font-semibold mb-2 text-gray-900 dark:text-white">{{ __('Как да генерирам справка?') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                        {{ __('1. Натиснете бутона "Генерирай справка" за желания тип вода') }}
                    </p>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                        {{ __('2. Изберете месец и година за справката') }}
                    </p>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('3. Натиснете бутона за създаване и изтеглете файла') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Generator Modal -->
    <x-filament::modal id="report-generator" width="md">
        <x-slot name="heading">{{ __('Генериране на справка') }}</x-slot>
        
        <x-slot name="description">
            {{ __('Изберете период за справката') }}
        </x-slot>

        <form id="reportForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" id="report_type" name="report_type" value="hot_water">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Месец') }}</label>
                    <select id="month" name="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="1">{{ __('Януари') }}</option>
                        <option value="2">{{ __('Февруари') }}</option>
                        <option value="3">{{ __('Март') }}</option>
                        <option value="4">{{ __('Април') }}</option>
                        <option value="5">{{ __('Май') }}</option>
                        <option value="6">{{ __('Юни') }}</option>
                        <option value="7">{{ __('Юли') }}</option>
                        <option value="8">{{ __('Август') }}</option>
                        <option value="9">{{ __('Септември') }}</option>
                        <option value="10">{{ __('Октомври') }}</option>
                        <option value="11">{{ __('Ноември') }}</option>
                        <option value="12">{{ __('Декември') }}</option>
                    </select>
                </div>
                
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Година') }}</label>
                    <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @php
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                                echo "<option value=\"{$year}\">{$year}</option>";
                            }
                        @endphp
                    </select>
                </div>
            </div>
        </form>

        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                x-on:click="isOpen = false"
            >
                {{ __('Отказ') }}
            </x-filament::button>

            <x-filament::button
                type="submit"
                form="reportForm"
                id="generate-report-button"
            >
                {{ __('Генерирай справка') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentMonth = new Date().getMonth() + 1;
            document.getElementById('month').value = currentMonth;
            
            // Set up the button handlers for hot and cold water reports
            Livewire.on('open-modal', ({ id, data }) => {
                if (id === 'report-generator' && data.type) {
                    document.getElementById('report_type').value = data.type;
                }
            });
            
            // Set up form submission
            document.getElementById('generate-report-button').addEventListener('click', function(e) {
                e.preventDefault();
                
                const reportType = document.getElementById('report_type').value;
                const month = document.getElementById('month').value;
                const year = document.getElementById('year').value;
                
                // Redirect to the report generation URL with parameters
                window.location.href = `{{ route('filament.admin.resources.reports.generate') }}?report_type=${reportType}&month=${month}&year=${year}`;
                
                // Close the modal
                Livewire.dispatch('close-modal', { id: 'report-generator' });
            });
        });
    </script>
</x-filament-panels::page>