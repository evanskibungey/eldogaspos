<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Cylinder Transactions</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage customer cylinder drop-offs and collections</p>
                </div>
                <a href="{{ route('pos.cylinders.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Transaction
                </a>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Active Drop-offs</p>
                            <p class="text-2xl font-semibold text-blue-600">{{ $stats['active_drop_offs'] }}</p>
                            <p class="text-xs text-gray-500">Waiting for collection</p>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Advance Collections</p>
                            <p class="text-2xl font-semibold text-purple-600">{{ $stats['active_advance_collections'] }}</p>
                            <p class="text-xs text-gray-500">Waiting for return</p>
                        </div>
                        <div class="p-2 bg-purple-50 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m0 0l-7 7-7-7z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Today Completed</p>
                            <p class="text-2xl font-semibold text-green-600">{{ $stats['today_completed'] }}</p>
                            <p class="text-xs text-gray-500">Transactions finished</p>
                        </div>
                        <div class="p-2 bg-green-50 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6">
                <form method="GET" action="{{ route('pos.cylinders.index') }}" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search by customer name, phone, or reference..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    
                    <div>
                        <select name="type" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">All Types</option>
                            <option value="drop_off" {{ request('type') === 'drop_off' ? 'selected' : '' }}>Drop-off</option>
                            <option value="advance_collection" {{ request('type') === 'advance_collection' ? 'selected' : '' }}>Advance Collection</option>
                        </select>
                    </div>
                    
                    <div>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Active Only</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors">
                        Filter
                    </button>
                    
                    @if(request()->hasAny(['search', 'status', 'type']))
                        <a href="{{ route('pos.cylinders.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cylinder</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waiting</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->reference_number }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->drop_off_date->format('M d, g:i A') }}</div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->customer_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->customer_phone }}</div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $transaction->cylinder_size }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->cylinder_type }}</div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->getTransactionTypeBadgeColor() }}">
                                            {{ $transaction->isDropOff() ? 'Drop-off' : 'Advance' }}
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">KSh {{ number_format($transaction->amount, 0) }}</div>
                                        @if($transaction->deposit_amount > 0)
                                            <div class="text-xs text-orange-600">+ KSh {{ number_format($transaction->deposit_amount, 0) }}</div>
                                        @endif
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transaction->getPaymentStatusBadgeColor() }}">
                                            {{ ucfirst($transaction->payment_status) }}
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->getDaysWaiting() }} days
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('pos.cylinders.show', $transaction) }}" 
                                               class="text-blue-600 hover:text-blue-900 transition-colors">
                                                View
                                            </a>
                                            
                                            @if($transaction->isActive())
                                                @if($transaction->isDropOff())
                                                    <button onclick="quickComplete({{ $transaction->id }})" 
                                                            class="text-green-600 hover:text-green-900 transition-colors">
                                                        Complete
                                                    </button>
                                                @else
                                                    <button onclick="quickReturn({{ $transaction->id }})" 
                                                            class="text-purple-600 hover:text-purple-900 transition-colors">
                                                        Return
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            <p class="text-lg font-medium text-gray-900 mb-1">No cylinder transactions found</p>
                                            <p class="text-sm text-gray-500">Get started by creating a new transaction</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($transactions->hasPages())
                    <div class="bg-white px-4 py-3 border-t border-gray-200">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="notification" class="fixed top-4 right-4 z-50" style="display: none;">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            <span id="notification-message"></span>
        </div>
    </div>

    <script>
        // Quick complete for drop-offs
        function quickComplete(transactionId) {
            if (confirm('Mark this cylinder as collected?')) {
                fetch(`/pos/cylinders/${transactionId}/quick-complete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
            }
        }

        // Quick return for advance collections
        function quickReturn(transactionId) {
            if (confirm('Process empty cylinder return and refund deposit?')) {
                fetch(`/pos/cylinders/${transactionId}/quick-return`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
            }
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const messageElement = document.getElementById('notification-message');
            
            messageElement.textContent = message;
            
            // Update colors based on type
            const container = notification.querySelector('div');
            if (type === 'error') {
                container.className = 'bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg';
            } else {
                container.className = 'bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg';
            }
            
            notification.style.display = 'block';
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</x-app-layout>