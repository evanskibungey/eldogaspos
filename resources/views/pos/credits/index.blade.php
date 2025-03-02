<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Customer Credit Management</h2>
                        <a href="{{ route('pos.dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Back to Dashboard
                        </a>
                    </div>
                    
                    <!-- Summary Card -->
                    <div class="bg-blue-50 p-4 rounded-md mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-blue-800">Credit Summary</h3>
                                <p class="text-sm text-blue-600">Total outstanding credit from {{ $customers->total() }} customers</p>
                            </div>
                            <div class="text-2xl font-bold text-blue-700">${{ number_format($totalCredit, 2) }}</div>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="mb-6">
                        <form action="{{ route('pos.credits.index') }}" method="GET">
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="Search by customer name or phone number..." 
                                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <button type="submit" class="absolute inset-y-0 right-0 flex items-center px-4 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700">
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Customers Table -->
                    @if($customers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Phone
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Credit Limit
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Balance Due
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($customers as $customer)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">{{ $customer->phone }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">${{ number_format($customer->credit_limit, 2) }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-red-700">${{ number_format($customer->balance, 2) }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $percentage = $customer->credit_limit > 0 
                                                        ? round(($customer->balance / $customer->credit_limit) * 100) 
                                                        : 100;
                                                @endphp
                                                
                                                <div class="text-xs">
                                                    <div class="flex items-center">
                                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                            <div class="h-2.5 rounded-full 
                                                                {{ $percentage < 50 ? 'bg-green-600' : ($percentage < 80 ? 'bg-yellow-500' : 'bg-red-600') }}" 
                                                                style="width: {{ min($percentage, 100) }}%;"></div>
                                                        </div>
                                                        <span class="ml-2 {{ $percentage < 50 ? 'text-green-700' : ($percentage < 80 ? 'text-yellow-700' : 'text-red-700') }}">
                                                            {{ $percentage }}%
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('pos.credits.show', $customer) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    View Details
                                                </a>
                                                <a href="{{ route('pos.credits.payment.form', $customer) }}" class="text-green-600 hover:text-green-900">
                                                    Record Payment
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            {{ $customers->links() }}
                        </div>
                    @else
                        <div class="text-center py-10">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No customers with outstanding credit</h3>
                            <p class="mt-1 text-sm text-gray-500">All customers have zero balance.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>