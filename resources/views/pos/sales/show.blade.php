<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Sale Details
                            </h2>
                            <p class="text-sm text-gray-500 mt-1 ml-8">Receipt: {{ $sale->receipt_number }}</p>
                        </div>
                        <div class="flex flex-wrap mt-4 md:mt-0 gap-2">
                            <a href="{{ route('pos.sales.history') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors duration-200 flex items-center shadow-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to History
                            </a>
                            <button onclick="window.print()" class="px-4 py-2 bg-[#0077B5] text-white rounded-md hover:bg-blue-700 transition-colors duration-200 flex items-center shadow-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print Receipt
                            </button>
                            
                            @if($sale->status == 'completed')
                                <form action="{{ route('pos.sales.void', $sale) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure you want to void this sale? This action cannot be undone.');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200 flex items-center shadow-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Void Sale
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Sale Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 4v12l-4-2-4 2V4M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Receipt Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-sm font-medium text-gray-500">Receipt Number:</div>
                                <div class="text-sm text-gray-900 font-semibold">{{ $sale->receipt_number }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Date:</div>
                                <div class="text-sm text-gray-900">{{ $sale->created_at->format('M d, Y h:i A') }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Cashier:</div>
                                <div class="text-sm text-gray-900">{{ $sale->user->name }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Payment Method:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_method == 'cash' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucfirst($sale->payment_method) }}
                                    </span>
                                </div>
                                
                                <div class="text-sm font-medium text-gray-500">Payment Status:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->payment_status == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($sale->payment_status) }}
                                    </span>
                                </div>
                                
                                <div class="text-sm font-medium text-gray-500">Status:</div>
                                <div class="text-sm text-gray-900">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $sale->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm border border-gray-100">
                            <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Customer Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-sm font-medium text-gray-500">Name:</div>
                                <div class="text-sm text-gray-900 font-semibold">{{ $sale->customer->name }}</div>
                                
                                <div class="text-sm font-medium text-gray-500">Phone:</div>
                                <div class="text-sm text-gray-900">{{ $sale->customer->phone }}</div>
                                
                                @if($sale->payment_method == 'credit')
                                    <div class="text-sm font-medium text-gray-500">Credit Limit:</div>
                                    <div class="text-sm text-gray-900">{{ $currencySymbol }}{{ number_format($sale->customer->credit_limit, 2) }}</div>
                                    
                                    <div class="text-sm font-medium text-gray-500">Current Balance:</div>
                                    <div class="text-sm text-gray-900">{{ $currencySymbol }}{{ number_format($sale->customer->balance, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sale Items -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center text-gray-800">
                            <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Sale Items
                        </h3>
                        <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-100">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Product
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Serial Number
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Unit Price
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($sale->items as $item)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $item->product->name }}
                                                <div class="text-xs text-gray-500">SKU: {{ $item->product->sku }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item->serial_number ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item->quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $currencySymbol }}{{ number_format($item->subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-sm font-medium text-gray-900 text-right">Total:</td>
                                        <td class="px-6 py-4 text-base font-bold text-gray-900">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Print-only Receipt -->
                    <div class="hidden print:block">
                        <div class="text-center mb-6">
                            <h2 class="text-2xl font-bold">{{ $companyName }}</h2>
                            @if(isset($companyAddress) && $companyAddress)
                                <p class="text-gray-600">{{ $companyAddress }}</p>
                            @endif
                            @if(isset($companyPhone) && $companyPhone)
                                <p class="text-gray-600">Phone: {{ $companyPhone }}</p>
                            @endif
                            @if(isset($companyEmail) && $companyEmail)
                                <p class="text-gray-600">Email: {{ $companyEmail }}</p>
                            @endif
                            <p class="text-gray-600 font-bold mt-2">Sales Receipt</p>
                            <p class="text-gray-600">{{ $sale->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        
                        <div class="mb-4 text-sm">
                            <table class="w-full">
                                <tr>
                                    <td class="w-1/2">
                                        <p><strong>Receipt #:</strong> {{ $sale->receipt_number }}</p>
                                        <p><strong>Cashier:</strong> {{ $sale->user->name }}</p>
                                        <p><strong>Payment:</strong> {{ ucfirst($sale->payment_method) }}</p>
                                    </td>
                                    <td class="w-1/2">
                                        <p><strong>Customer:</strong> {{ $sale->customer->name }}</p>
                                        <p><strong>Phone:</strong> {{ $sale->customer->phone }}</p>
                                        <p><strong>Status:</strong> {{ ucfirst($sale->status) }}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="border-t border-b py-4 mb-4">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left">
                                        <th class="pb-2">Item</th>
                                        <th class="pb-2">S/N</th>
                                        <th class="pb-2 text-right">Qty</th>
                                        <th class="pb-2 text-right">Price</th>
                                        <th class="pb-2 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->items as $item)
                                        <tr>
                                            <td class="py-2">{{ $item->product->name }}</td>
                                            <td class="py-2">{{ $item->serial_number ?? 'N/A' }}</td>
                                            <td class="py-2 text-right">{{ $item->quantity }}</td>
                                            <td class="py-2 text-right">{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="py-2 text-right">{{ $currencySymbol }}{{ number_format($item->subtotal, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mb-4">
                            <table class="w-full">
                                <tr>
                                    <td class="w-4/5 text-right"><strong>Subtotal:</strong></td>
                                    <td class="w-1/5 text-right">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                                </tr>
                                @if(isset($taxPercentage) && $taxPercentage > 0)
                                    <tr>
                                        <td class="w-4/5 text-right"><strong>Tax ({{ $taxPercentage }}%):</strong></td>
                                        <td class="w-1/5 text-right">{{ $currencySymbol }}{{ number_format(($sale->total_amount * $taxPercentage / 100), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="w-4/5 text-right"><strong>Total:</strong></td>
                                        <td class="w-1/5 text-right font-bold">{{ $currencySymbol }}{{ number_format($sale->total_amount * (1 + $taxPercentage / 100), 2) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td class="w-4/5 text-right"><strong>Total:</strong></td>
                                        <td class="w-1/5 text-right font-bold">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        
                        <div class="text-center text-sm mt-8">
                            @if(isset($receiptFooter) && $receiptFooter)
                                <p>{{ $receiptFooter }}</p>
                            @else
                                <p>Thank you for your business!</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>