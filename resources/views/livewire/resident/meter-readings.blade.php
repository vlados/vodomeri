<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h1 class="text-2xl font-semibold mb-6">Reading History</h1>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="meterType" class="block text-sm font-medium text-gray-700">Water Meter Type</label>
                            <select id="meterType" wire:model.live="filter.meter_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Meters</option>
                                <option value="hot">Hot Water</option>
                                <option value="cold">Cold Water</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" wire:model.live="filter.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="dateRange" class="block text-sm font-medium text-gray-700">Date Range</label>
                            <select id="dateRange" wire:model.live="filter.date_range" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="all">All Time</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="last_3_months">Last 3 Months</option>
                                <option value="last_6_months">Last 6 Months</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                @if ($readings->isEmpty())
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    No readings found matching your criteria.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Apartment
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Meter Type
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reading Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reading Value
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Consumption
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Submitted By
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($readings as $reading)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            #{{ $reading->waterMeter->apartment->number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $reading->waterMeter->type === 'hot' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ $reading->waterMeter->type === 'hot' ? 'Hot' : 'Cold' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $reading->reading_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($reading->value, 3) }} m³
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ number_format($reading->consumption, 3) }} m³
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $reading->status === 'approved' ? 'bg-green-100 text-green-800' : 
                                                   ($reading->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($reading->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $reading->user->name }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $readings->links() }}
                    </div>
                @endif
                
                <div class="mt-6">
                    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        &larr; Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
