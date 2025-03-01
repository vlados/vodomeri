<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-semibold mb-6">Submit Multiple Readings</h1>
                
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
                                    You do not have any apartments assigned to your account. Please contact the administrator.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            &larr; Back to Dashboard
                        </a>
                    </div>
                @else
                    <form wire:submit="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="selectedApartmentId" class="block text-sm font-medium text-gray-700">Select Apartment</label>
                                <select id="selectedApartmentId" wire:model.live="selectedApartmentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach ($apartments as $apartment)
                                        <option value="{{ $apartment->id }}">Apartment #{{ $apartment->number }} (Floor {{ $apartment->floor }})</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label for="readingDate" class="block text-sm font-medium text-gray-700">Reading Date</label>
                                <input type="date" id="readingDate" wire:model="readingDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" max="{{ now()->format('Y-m-d') }}">
                                @error('readingDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        @if (empty($meters))
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <p class="text-sm text-yellow-700">
                                    No water meters found for this apartment.
                                </p>
                            </div>
                        @else
                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <p class="text-sm text-gray-600 mb-2">
                                    Enter readings for each water meter below. You can leave fields blank for meters you don't want to submit readings for.
                                </p>
                                <p class="text-sm text-gray-600">
                                    All readings will use the same date specified above.
                                </p>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Water Meter
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Previous Reading
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                New Reading
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Photo
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Notes
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($meters as $index => $meter)
                                            <tr class="{{ $meter['type'] === 'hot' ? 'bg-red-50' : 'bg-blue-50' }}">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full {{ $meter['type'] === 'hot' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600' }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                                                            </svg>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{ $meter['type'] === 'hot' ? 'Hot' : 'Cold' }} Water
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                S/N: {{ $meter['serial_number'] }}
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                Location: {{ $meter['location'] ?: 'Not specified' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">{{ number_format($meter['previous_value'], 3) }} m³</div>
                                                    <div class="text-xs text-gray-500">{{ $meter['previous_date'] }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="relative rounded-md shadow-sm">
                                                        <input type="number" id="meter-value-{{ $index }}" wire:model="meters.{{ $index }}.value" step="0.001" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="000.000">
                                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                            <span class="text-gray-500 sm:text-sm">m³</span>
                                                        </div>
                                                    </div>
                                                    @error("meters.{$index}.value") <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                                </td>
                                                <td class="px-6 py-4">
                                                    <input type="file" id="meter-photo-{{ $index }}" wire:model="meters.{{ $index }}.photo" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
                                                    @error("meters.{$index}.photo") <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                                </td>
                                                <td class="px-6 py-4">
                                                    <textarea id="meter-notes-{{ $index }}" wire:model="meters.{{ $index }}.notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Any additional details..."></textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        
                        <div class="pt-4 flex items-center justify-between">
                            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                Cancel
                            </a>
                            
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Submit All Readings
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>