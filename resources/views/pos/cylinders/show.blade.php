<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Cylinder Transaction</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $cylinder->reference_number }}</p>
                </div>
                <a href="{{ route('pos.cylinders.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to List
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Transaction Overview -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Transaction Details
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Reference Number</label>
                                <p class="mt-1 text-sm text-gray-900 font-mono">{{ $cylinder->reference_number }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Transaction Type</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cylinder->getTransactionTypeBadgeColor() }}">
                                        {{ $cylinder->isDropOff() ? 'Drop-off First' : 'Advance Collection' }}
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Cylinder Details</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $cylinder->cylinder_size }} {{ $cylinder->cylinder_type }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status</label>
                                <div class="mt-1 flex space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cylinder->getStatusBadgeColor() }}">
                                        {{ ucfirst($cylinder->status) }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cylinder->getPaymentStatusBadgeColor() }}">
                                        {{ ucfirst($cylinder->payment_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                            Payment Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Gas Refill Amount</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">KSh {{ number_format($cylinder->amount, 0) }}</p>
                            </div>
                            
                            @if($cylinder->deposit_amount > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Deposit Amount</label>
                                    <p class="mt-1 text-lg font-semibold text-orange-600">KSh {{ number_format($cylinder->deposit_amount, 0) }}</p>
                                </div>
                            @endif
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Total Amount</label>
                                <p class="mt-1 text-xl font-bold text-green-600">KSh {{ number_format($cylinder->getTotalAmount(), 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Process Instructions -->
                    @if($cylinder->isActive())
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Next Steps
                            </h3>
                            
                            @if($cylinder->isDropOff())
                                <div class="space-y-2">
                                    <p class="text-sm text-blue-800">
                                        <strong>Customer should collect refilled cylinder:</strong>
                                    </p>
                                    <ul class="text-sm text-blue-700 space-y-1 ml-4">
                                        <li>• Verify customer identity</li>
                                        <li>• Confirm payment if pending</li>
                                        <li>• Hand over refilled cylinder</li>
                                        <li>• Mark transaction as complete</li>
                                    </ul>
                                </div>
                            @else
                                <div class="space-y-2">
                                    <p class="text-sm text-blue-800">
                                        <strong>Customer should return empty cylinder:</strong>
                                    </p>
                                    <ul class="text-sm text-blue-700 space-y-1 ml-4">
                                        <li>• Receive empty cylinder from customer</li>
                                        <li>• Process deposit refund</li>
                                        @if($cylinder->isPending())
                                            <li>• Collect payment for gas refill</li>
                                        @endif
                                        <li>• Mark transaction as complete</li>
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($cylinder->notes)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                Notes
                            </h3>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $cylinder->notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Customer Information
                        </h3>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $cylinder->customer_name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $cylinder->customer_phone }}</p>
                            </div>
                            
                            @if($cylinder->customer->balance > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Current Balance</label>
                                    <p class="mt-1 text-sm font-semibold text-red-600">KSh {{ number_format($cylinder->customer->balance, 0) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    @if($cylinder->isActive())
                        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                            
                            <div class="space-y-3">
                                @if($cylinder->isDropOff())
                                    <!-- Mark as Collected -->
                                    <button onclick="quickComplete()" 
                                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Mark as Collected
                                    </button>
                                @else
                                    <!-- Process Return -->
                                    <button onclick="quickReturn()" 
                                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3"/>
                                        </svg>
                                        Process Return
                                    </button>
                                @endif

                                <!-- Detailed Complete Form -->
                                <button onclick="showCompleteForm()" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Complete with Details
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Transaction Timeline -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Timeline</h4>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center">
                                <div class="h-2 w-2 bg-blue-500 rounded-full mr-3"></div>
                                <div>
                                    <p class="text-gray-900 font-medium">
                                        {{ $cylinder->isDropOff() ? 'Cylinder Dropped Off' : 'Gas Collected' }}
                                    </p>
                                    <p class="text-gray-500 text-xs">{{ $cylinder->drop_off_date->format('M d, Y \a\t g:i A') }}</p>
                                </div>
                            </div>
                            
                            @if($cylinder->collection_date || $cylinder->return_date)
                                <div class="flex items-center">
                                    <div class="h-2 w-2 bg-green-500 rounded-full mr-3"></div>
                                    <div>
                                        <p class="text-gray-900 font-medium">
                                            {{ $cylinder->isDropOff() ? 'Cylinder Collected' : 'Empty Returned' }}
                                        </p>
                                        <p class="text-gray-500 text-xs">
                                            {{ ($cylinder->collection_date ?? $cylinder->return_date)->format('M d, Y \a\t g:i A') }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center">
                                    <div class="h-2 w-2 bg-yellow-500 rounded-full mr-3"></div>
                                    <div>
                                        <p class="text-gray-900 font-medium">
                                            Waiting {{ $cylinder->getDaysWaiting() }} days
                                        </p>
                                        <p class="text-gray-500 text-xs">
                                            For {{ $cylinder->isDropOff() ? 'collection' : 'return' }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Form Modal -->
    <div id="completeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Complete Transaction</h3>
                <form method="POST" action="{{ route('pos.cylinders.complete', $cylinder) }}">
                    @csrf
                    
                    @if($cylinder->isDropOff() && $cylinder->isPending())
                        <div class="mb-4">
                            <label class="text-sm font-medium text-gray-700">Payment Status</label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center">
                                    <input id="modal_paid" name="payment_status" type="radio" value="paid" 
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                    <label for="modal_paid" class="ml-2 block text-sm text-gray-900">
                                        Paid - Customer paid now
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="modal_pending" name="payment_status" type="radio" value="pending" checked
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                    <label for="modal_pending" class="ml-2 block text-sm text-gray-900">
                                        Still Pending
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <label for="modal_notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                        <textarea name="notes" id="modal_notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                  placeholder="Any additional notes...">{{ $cylinder->notes }}</textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideCompleteForm()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            Complete Transaction
                        </button>
                    </div>
                </form>
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
        function quickComplete() {
            if (confirm('Mark this cylinder as collected?')) {
                fetch(`/pos/cylinders/{{ $cylinder->id }}/quick-complete`, {
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
                        setTimeout(() => location.href = '{{ route("pos.cylinders.index") }}', 1500);
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
        function quickReturn() {
            if (confirm('Process empty cylinder return and refund deposit?')) {
                fetch(`/pos/cylinders/{{ $cylinder->id }}/quick-return`, {
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
                        setTimeout(() => location.href = '{{ route("pos.cylinders.index") }}', 1500);
                    } else {
                        showNotification(data.error || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    showNotification('An error occurred', 'error');
                });
            }
        }

        // Show complete form modal
        function showCompleteForm() {
            document.getElementById('completeModal').classList.remove('hidden');
        }

        // Hide complete form modal
        function hideCompleteForm() {
            document.getElementById('completeModal').classList.add('hidden');
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

        // Close modal when clicking outside
        document.getElementById('completeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCompleteForm();
            }
        });
    </script>
</x-app-layout>