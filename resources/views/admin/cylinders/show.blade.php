<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Cylinder Transaction Details</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $cylinder->reference_number }}</p>
                </div>
                <div class="flex space-x-3">
                    @if($cylinder->isActive())
                        <a href="{{ route('admin.cylinders.edit', $cylinder) }}" 
                           class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </a>
                    @endif
                    <a href="{{ route('admin.cylinders.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to List
                    </a>
                </div>
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

                    <!-- Timeline -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Timeline
                        </h3>
                        
                        <div class="flow-root">
                            <ul class="mb-8">
                                <!-- Drop-off/Initial Collection -->
                                <li>
                                    <div class="relative pb-8">
                                        <div class="relative flex items-start space-x-3">
                                            <div class="relative">
                                                <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div>
                                                    <div class="text-sm">
                                                        <span class="font-medium text-gray-900">
                                                            {{ $cylinder->isDropOff() ? 'Cylinder Dropped Off' : 'Gas Collected (Advance)' }}
                                                        </span>
                                                    </div>
                                                    <p class="mt-0.5 text-sm text-gray-500">
                                                        {{ $cylinder->drop_off_date->format('M d, Y \a\t g:i A') }}
                                                    </p>
                                                    <p class="mt-0.5 text-sm text-gray-500">
                                                        By {{ $cylinder->createdBy->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Collection/Return -->
                                @if($cylinder->collection_date || $cylinder->return_date)
                                    <li>
                                        <div class="relative pb-8">
                                            <div class="relative flex items-start space-x-3">
                                                <div class="relative">
                                                    <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div>
                                                        <div class="text-sm">
                                                            <span class="font-medium text-gray-900">
                                                                {{ $cylinder->isDropOff() ? 'Refilled Cylinder Collected' : 'Empty Cylinder Returned' }}
                                                            </span>
                                                        </div>
                                                        <p class="mt-0.5 text-sm text-gray-500">
                                                            {{ ($cylinder->collection_date ?? $cylinder->return_date)->format('M d, Y \a\t g:i A') }}
                                                        </p>
                                                        @if($cylinder->completedBy)
                                                            <p class="mt-0.5 text-sm text-gray-500">
                                                                By {{ $cylinder->completedBy->name }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @else
                                    <li>
                                        <div class="relative">
                                            <div class="relative flex items-start space-x-3">
                                                <div class="relative">
                                                    <div class="h-10 w-10 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white">
                                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div>
                                                        <div class="text-sm">
                                                            <span class="font-medium text-gray-900">
                                                                Waiting for {{ $cylinder->isDropOff() ? 'Customer Collection' : 'Empty Cylinder Return' }}
                                                            </span>
                                                        </div>
                                                        <p class="mt-0.5 text-sm text-gray-500">
                                                            {{ $cylinder->getDaysWaiting() }} days waiting
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

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

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('admin.customers.show', $cylinder->customer) }}" 
                               class="text-sm text-blue-600 hover:text-blue-900 transition-colors">
                                View customer profile →
                            </a>
                        </div>
                    </div>

                    <!-- Actions -->
                    @if($cylinder->isActive())
                        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                            
                            <div class="space-y-3">
                                <!-- Complete Transaction -->
                                <form method="POST" action="{{ route('admin.cylinders.complete', $cylinder) }}" 
                                      onsubmit="return confirm('Are you sure you want to complete this transaction?')">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Complete Transaction
                                    </button>
                                </form>

                                <!-- Cancel Transaction -->
                                <form method="POST" action="{{ route('admin.cylinders.cancel', $cylinder) }}" 
                                      onsubmit="return confirm('Are you sure you want to cancel this transaction? This action cannot be undone.')">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Cancel Transaction
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Transaction Summary -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Quick Summary</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created:</span>
                                <span class="text-gray-900">{{ $cylinder->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Days Active:</span>
                                <span class="text-gray-900">{{ $cylinder->getDaysWaiting() }} days</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Staff:</span>
                                <span class="text-gray-900">{{ $cylinder->createdBy->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>