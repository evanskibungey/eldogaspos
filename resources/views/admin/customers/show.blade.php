<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Customer Profile</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $customer->name }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.customers.edit', $customer) }}" 
                       class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Customer
                    </a>
                    <a href="{{ route('admin.customers.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to Customers
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Customer Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Customer Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $customer->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $customer->phone }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Credit Limit</label>
                                <p class="mt-1 text-sm text-gray-900">KSh {{ number_format($customer->credit_limit, 2) }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Current Balance</label>
                                <p class="mt-1 text-sm font-semibold {{ $customer->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    KSh {{ number_format($customer->balance, 2) }}
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $customer->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($customer->status) }}
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Member Since</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $customer->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Recent Sales
                        </h3>
                        
                        @if($recentSales->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($recentSales as $sale)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $sale->receipt_number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $sale->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $sale->items->count() }} items
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                KSh {{ number_format($sale->total_amount, 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-4">No sales history found</p>
                        @endif
                    </div>

                    <!-- Recent Cylinder Transactions -->
                    @if($recentCylinders->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Cylinder Transactions
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cylinder</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentCylinders as $cylinder)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('admin.cylinders.show', $cylinder) }}" class="text-blue-600 hover:text-blue-900">
                                                {{ $cylinder->reference_number }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cylinder->getTransactionTypeBadgeColor() }}">
                                                {{ $cylinder->isDropOff() ? 'Drop-off' : 'Advance' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $cylinder->cylinder_size }} {{ $cylinder->cylinder_type }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cylinder->getStatusBadgeColor() }}">
                                                {{ ucfirst($cylinder->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            KSh {{ number_format($cylinder->amount, 2) }}
                                            @if($cylinder->deposit_amount > 0)
                                                <br><span class="text-xs text-orange-600">+ KSh {{ number_format($cylinder->deposit_amount, 2) }} deposit</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Total Sales</span>
                                <span class="text-sm font-medium text-gray-900">{{ $stats['total_sales'] }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Total Spent</span>
                                <span class="text-sm font-medium text-gray-900">KSh {{ number_format($stats['total_spent'], 2) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Current Balance</span>
                                <span class="text-sm font-medium {{ $stats['pending_credit'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    KSh {{ number_format($stats['pending_credit'], 2) }}
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Active Cylinders</span>
                                <span class="text-sm font-medium text-gray-900">{{ $stats['active_cylinders'] }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Total Cylinders</span>
                                <span class="text-sm font-medium text-gray-900">{{ $stats['total_cylinders'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Management -->
                    @if($customer->balance != 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Balance Management</h3>
                        
                        <div class="mb-4 p-3 rounded-lg {{ $customer->balance > 0 ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' }}">
                            <p class="text-sm font-medium {{ $customer->balance > 0 ? 'text-red-800' : 'text-green-800' }}">
                                Current Balance: KSh {{ number_format($customer->balance, 2) }}
                            </p>
                            @if($customer->balance > 0)
                                <p class="text-xs text-red-600 mt-1">Customer owes money</p>
                            @else
                                <p class="text-xs text-green-600 mt-1">Customer has credit</p>
                            @endif
                        </div>
                        
                        <button onclick="showBalanceModal()" 
                                class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md transition-colors text-sm font-medium">
                            Adjust Balance
                        </button>
                    </div>
                    @endif

                    <!-- Active Cylinder Transactions -->
                    @if($activeCylinders->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Active Cylinders</h3>
                        
                        <div class="space-y-3">
                            @foreach($activeCylinders as $cylinder)
                            <div class="border border-gray-200 rounded-lg p-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $cylinder->cylinder_size }} {{ $cylinder->cylinder_type }}</p>
                                        <p class="text-xs text-gray-500">{{ $cylinder->reference_number }}</p>
                                        <p class="text-xs text-gray-500">{{ $cylinder->getDaysWaiting() }} days waiting</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $cylinder->getTransactionTypeBadgeColor() }}">
                                        {{ $cylinder->isDropOff() ? 'Drop-off' : 'Advance' }}
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.cylinders.show', $cylinder) }}" 
                                       class="text-xs text-blue-600 hover:text-blue-900">
                                        View Details →
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Adjustment Modal -->
    <div id="balanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Adjust Customer Balance</h3>
                <form method="POST" action="{{ route('admin.customers.adjust-balance', $customer) }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
                        <select name="adjustment_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                            <option value="add">Add to Balance</option>
                            <option value="subtract">Subtract from Balance</option>
                            <option value="set">Set Balance To</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount (KSh)</label>
                        <input type="number" name="amount" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Reason for adjustment..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideBalanceModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700">
                            Adjust Balance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showBalanceModal() {
            document.getElementById('balanceModal').classList.remove('hidden');
        }
        
        function hideBalanceModal() {
            document.getElementById('balanceModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('balanceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideBalanceModal();
            }
        });
    </script>
</x-app-layout>