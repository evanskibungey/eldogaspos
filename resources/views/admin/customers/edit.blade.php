@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $showRoute = 'admin.cylinders.show';

    // Handle return URL for navigation back to filtered list
    $hasReturnUrl = isset($returnParams) && !empty($returnParams['return_url']);
    $backUrl = $hasReturnUrl
        ? $returnParams['return_url'] . '?' . http_build_query(array_filter([
            'period' => $returnParams['period'] ?? null,
            'start_date' => $returnParams['start_date'] ?? null,
            'end_date' => $returnParams['end_date'] ?? null,
        ]))
        : route($showRoute, $cylinder);
    $backLabel = $hasReturnUrl ? 'Back to List' : 'Back to Details';
@endphp

<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Edit Cylinder Transaction</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $cylinder->reference_number }}</p>
                </div>
                <a href="{{ $backUrl }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ $backLabel }}
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Transaction Overview (Read-only) -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Transaction Details (Read-only)
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
                                        {{ $cylinder->isDropOff() ? 'Drop-off' : 'Advance Collection' }}
                                    </span>
                                </p>
                            </div>

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
                                <label class="block text-sm font-medium text-gray-500">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $cylinder->getStatusBadgeColor() }}">
                                        {{ ucfirst($cylinder->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Products Included (Read-only) -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            Products Included (Read-only)
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($cylinder->items as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <div>
                                                        <p class="font-medium">{{ $item->product->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ $item->product->category->name }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $item->brand ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $item->quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                KSh {{ number_format($item->unit_price, 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                KSh {{ number_format($item->subtotal, 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Editable Form -->
                    <form method="POST" action="{{ route('admin.cylinders.update', $cylinder) }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        @csrf
                        @method('PUT')

                        @if(isset($returnParams) && !empty($returnParams['return_url']))
                            <input type="hidden" name="return_url" value="{{ $returnParams['return_url'] }}">
                            @if(!empty($returnParams['period']))
                                <input type="hidden" name="period" value="{{ $returnParams['period'] }}">
                            @endif
                            @if(!empty($returnParams['start_date']))
                                <input type="hidden" name="start_date" value="{{ $returnParams['start_date'] }}">
                            @endif
                            @if(!empty($returnParams['end_date']))
                                <input type="hidden" name="end_date" value="{{ $returnParams['end_date'] }}">
                            @endif
                        @endif

                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Details
                        </h3>

                        <div class="space-y-6">
                            <!-- Payment Status Section -->
                            <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-xl p-6 border-2 border-orange-200">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-orange-500 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <label class="block text-base font-bold text-gray-900">
                                            Payment Status
                                            <span class="text-red-600">*</span>
                                        </label>
                                        <p class="text-xs text-gray-600 mt-0.5">Update payment status for this transaction</p>
                                    </div>
                                </div>

                                <!-- Payment Amount Summary -->
                                <div class="bg-white rounded-lg p-4 mb-4 border border-orange-100">
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Gas Refill Amount:</span>
                                            <span class="text-base font-bold text-gray-900">KSh {{ number_format($cylinder->amount, 0) }}</span>
                                        </div>
                                        @if($cylinder->deposit_amount > 0)
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm text-gray-600">Deposit Amount:</span>
                                                <span class="text-base font-bold text-orange-600">KSh {{ number_format($cylinder->deposit_amount, 0) }}</span>
                                            </div>
                                            <div class="pt-2 border-t border-gray-200">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-semibold text-gray-700">Total Amount:</span>
                                                    <span class="text-lg font-bold text-green-600">KSh {{ number_format($cylinder->getTotalAmount(), 0) }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Payment Status Options -->
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="payment_status" value="paid" class="sr-only peer"
                                               {{ $cylinder->payment_status === 'paid' ? 'checked' : '' }}>
                                        <div class="p-4 border-2 border-gray-300 rounded-xl peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-400 transition-all duration-200 peer-checked:shadow-lg">
                                            <div class="flex flex-col items-center text-center">
                                                <div class="w-12 h-12 bg-green-100 peer-checked:bg-green-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                    <svg class="w-6 h-6 text-green-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                                <p class="font-bold text-gray-900">Paid</p>
                                                <p class="text-xs text-gray-600 mt-1">Payment received</p>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="cursor-pointer group">
                                        <input type="radio" name="payment_status" value="pending" class="sr-only peer"
                                               {{ $cylinder->payment_status === 'pending' ? 'checked' : '' }}>
                                        <div class="p-4 border-2 border-gray-300 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-400 transition-all duration-200 peer-checked:shadow-lg">
                                            <div class="flex flex-col items-center text-center">
                                                <div class="w-12 h-12 bg-orange-100 peer-checked:bg-orange-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                                    <svg class="w-6 h-6 text-orange-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                                <p class="font-bold text-gray-900">Pending</p>
                                                <p class="text-xs text-gray-600 mt-1">Pay later</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>

                                @error('payment_status')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <!-- Current Status Display -->
                                <div class="mt-4 p-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">Current Status:</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $cylinder->getPaymentStatusBadgeColor() }}">
                                            {{ ucfirst($cylinder->payment_status) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Important Notes -->
                                @if($cylinder->isPending() && $cylinder->isAdvanceCollection())
                                    <div class="mt-4 p-4 bg-blue-50 border-2 border-blue-300 rounded-lg">
                                        <div class="flex items-start">
                                            <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-semibold text-blue-900">Payment Impact</p>
                                                <p class="text-xs text-blue-800 mt-1">
                                                    Marking as <strong>paid</strong> will deduct <strong>KSh {{ number_format($cylinder->amount, 0) }}</strong> from customer balance.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($cylinder->customer->balance > 0)
                                    <div class="mt-3 p-3 bg-yellow-50 border border-yellow-300 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <p class="text-xs text-yellow-800">
                                                Customer has outstanding balance: <strong>KSh {{ number_format($cylinder->customer->balance, 0) }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Notes -->
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes
                                </label>
                                <textarea id="notes" name="notes" rows="4" placeholder="Add any notes about this transaction..."
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent @error('notes') border-red-500 @enderror">{{ old('notes', $cylinder->notes) }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Max 1000 characters</p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex gap-3 pt-6 mt-6 border-t border-gray-200">
                            <button type="submit" class="flex-1 inline-flex justify-center items-center px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save Changes
                            </button>
                            <a href="{{ $backUrl }}" class="flex-1 inline-flex justify-center items-center px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Discard Changes
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Customer Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Customer
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Name</label>
                                <p class="mt-1 text-sm text-gray-900 font-medium">{{ $cylinder->customer_name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Phone</label>
                                <p class="mt-1 text-sm text-gray-900 font-medium">{{ $cylinder->customer_phone }}</p>
                            </div>

                            @if($cylinder->customer->balance > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Current Balance</label>
                                    <p class="mt-1 text-sm font-semibold text-red-600">KSh {{ number_format($cylinder->customer->balance, 0) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- What Can Be Edited -->
                    <div class="bg-green-50 rounded-lg border border-green-200 p-4">
                        <h4 class="text-sm font-bold text-green-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Editable Fields
                        </h4>
                        <ul class="space-y-2 text-sm text-green-800">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mt-0.5 mr-2 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Payment Status</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 mt-0.5 mr-2 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Notes</span>
                            </li>
                        </ul>
                    </div>

                    <!-- What Cannot Be Edited -->
                    <div class="bg-yellow-50 rounded-lg border border-yellow-200 p-4">
                        <h4 class="text-sm font-bold text-yellow-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Protected Fields
                        </h4>
                        <ul class="space-y-1 text-sm text-yellow-800">
                            <li>• Transaction Type</li>
                            <li>• Products & Quantities</li>
                            <li>• Customer Information</li>
                            <li>• Amounts</li>
                        </ul>
                        <p class="mt-2 text-xs text-yellow-700">Inventory has already been adjusted for this transaction.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>