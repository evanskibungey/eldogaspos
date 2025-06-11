<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Customer Credit Details</h2>
                        <div class="flex space-x-2">
                            <a href="{{ route('pos.credits.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                Back to Credit List
                            </a>
                            <a href="{{ route('pos.credits.payment.form', $customer) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Record Payment
                            </a>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-gray-50 p-4 rounded-md">
                            <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="text-sm font-medium text-gray-500">Name:</div>
                                <div class="text-sm text-gray-900">{{ $customer->name }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Phone:</div>
                                <div class="text-sm text-gray-900">{{ $customer->phone }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Status:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $customer->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($customer->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-md">
                            <h3 class="text-lg font-semibold text-blue-800 mb-4">Credit Summary</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="text-sm font-medium text-blue-600">Credit Limit:</div>
                                <div class="text-sm text-blue-900">{{ $currencySymbol }} {{ number_format($customer->credit_limit, 2) }}</div>
                                
                                <div class="text-sm font-medium text-blue-600">Current Balance:</div>
                                <div class="text-sm font-bold text-red-600">{{ $currencySymbol }} {{ number_format($customer->balance, 2) }}</div>
                                
                                <div class="text-sm font-medium text-blue-600">Available Credit:</div>
                                <div class="text-sm {{ ($customer->credit_limit - $customer->balance) > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $currencySymbol }} {{ number_format(max(0, $customer->credit_limit - $customer->balance), 2) }}
                                </div>
                                
                                <div class="text-sm font-medium text-blue-600">Usage:</div>
                                <div class="text-sm text-blue-900">
                                    @php
                                        $percentage = $customer->credit_limit > 0 
                                            ? round(($customer->balance / $customer->credit_limit) * 100) 
                                            : 100;
                                    @endphp
                                    
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
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-md">
                            <h3 class="text-lg font-semibold text-green-800 mb-4">Payment Summary</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="text-sm font-medium text-green-600">Total Sales:</div>
                                <div class="text-sm text-green-900">{{ $currencySymbol }} {{ number_format($creditSales->sum('total_amount'), 2) }}</div>
                                
                                <div class="text-sm font-medium text-green-600">Total Paid:</div>
                                <div class="text-sm text-green-900">{{ $currencySymbol }} {{ number_format($payments->sum('amount'), 2) }}</div>
                                
                                <div class="text-sm font-medium text-green-600">Last Payment:</div>
                                <div class="text-sm text-green-900">
                                    {{ $payments->count() > 0 ? $payments->first()->created_at->format('M d, Y') : 'No payments' }}
                                </div>
                                
                                <div class="text-sm font-medium text-green-600">Payment Status:</div>
                                <div class="text-sm">
                                    @if($customer->balance <= 0)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Fully Paid
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Outstanding
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabs for Credit Sales and Payments -->
                    <div x-data="{ tab: 'sales' }">
                        <!-- Tab Navigation -->
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex space-x-8">
                                <a @click.prevent="tab = 'sales'" href="#" 
                                   :class="{ 'border-blue-500 text-blue-600': tab === 'sales', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'sales' }"
                                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Credit Sales ({{ $creditSales->count() }})
                                </a>
                                <a @click.prevent="tab = 'payments'" href="#" 
                                   :class="{ 'border-blue-500 text-blue-600': tab === 'payments', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'payments' }"
                                   class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Payment History ({{ $payments->count() }})
                                </a>
                            </nav>
                        </div>
                        
                        <!-- Sales Tab Content -->
                        <div x-show="tab === 'sales'">
                            <h3 class="text-lg font-semibold mb-4">Credit Sales</h3>
                            
                            @if($creditSales->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Receipt #
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Items
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Amount
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
                                            @foreach($creditSales as $sale)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ $sale->receipt_number }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $sale->created_at->format('M d, Y h:i A') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $sale->items->count() }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ $currencySymbol }} {{ number_format($sale->total_amount, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            {{ $sale->payment_status == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                            {{ ucfirst($sale->payment_status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <a href="{{ route('pos.sales.show', $sale) }}" class="text-blue-600 hover:text-blue-900">
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-10 bg-gray-50 rounded-md">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No credit sales found</h3>
                                    <p class="mt-1 text-sm text-gray-500">This customer hasn't made any credit purchases yet.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Payments Tab Content -->
                        <div x-show="tab === 'payments'" style="display: none;">
                            <h3 class="text-lg font-semibold mb-4">Payment History</h3>
                            
                            @if($payments->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Amount
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Method
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Reference
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Recorded By
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Notes
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($payments as $payment)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $payment->created_at->format('M d, Y h:i A') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-700">
                                                        {{ $currencySymbol }} {{ number_format($payment->amount, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $payment->reference_number ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $payment->user->name }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $payment->notes ?? 'No notes' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-10 bg-gray-50 rounded-md">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No payments recorded</h3>
                                    <p class="mt-1 text-sm text-gray-500">This customer hasn't made any payments yet.</p>
                                    <div class="mt-6">
                                        <a href="{{ route('pos.credits.payment.form', $customer) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                            </svg>
                                            Record First Payment
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>