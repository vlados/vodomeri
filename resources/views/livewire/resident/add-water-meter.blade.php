<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-semibold mb-6">Add New Water Meter</h1>
                
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
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <p class="text-sm text-gray-600">
                            Use this form to register a new water meter for your apartment. After submission, the meter will be reviewed by an administrator.
                        </p>
                    </div>
                    
                    <form wire:submit="save" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="selectedApartmentId" class="block text-sm font-medium text-gray-700">Apartment</label>
                                <select id="selectedApartmentId" wire:model="selectedApartmentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach ($apartments as $apartment)
                                        <option value="{{ $apartment->id }}">Apartment #{{ $apartment->number }} (Floor {{ $apartment->floor }})</option>
                                    @endforeach
                                </select>
                                @error('selectedApartmentId') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Meter Type</label>
                                <select id="type" wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="cold">Cold Water</option>
                                    <option value="hot">Hot Water</option>
                                </select>
                                @error('type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label for="serialNumber" class="block text-sm font-medium text-gray-700">Serial Number</label>
                            <input type="text" id="serialNumber" wire:model="serialNumber" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. SN12345678">
                            @error('serialNumber') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700">Location (optional)</label>
                            <input type="text" id="location" wire:model="location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Bathroom, Kitchen">
                            @error('location') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="installationDate" class="block text-sm font-medium text-gray-700">Installation Date</label>
                                <input type="date" id="installationDate" wire:model="installationDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" max="{{ now()->format('Y-m-d') }}">
                                @error('installationDate') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="initialReading" class="block text-sm font-medium text-gray-700">Initial Reading (m³)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="number" id="initialReading" wire:model="initialReading" step="0.001" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="000.000">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">m³</span>
                                    </div>
                                </div>
                                @error('initialReading') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="pt-4 flex items-center justify-between">
                            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                Cancel
                            </a>
                            
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Add Water Meter
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>