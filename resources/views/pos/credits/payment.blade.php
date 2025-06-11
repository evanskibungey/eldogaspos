<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Record Payment</h2>
                        <a href="{{ route('pos.credits.show', $customer) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Back to Customer
                        </a>
                    </div>
                    
                    <!-- Customer Info and Balance Card -->
                    <div class="bg-blue-50 p-4 rounded-md mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">Customer</h3>
                                <p class="text-lg font-semibold text-blue-900">{{ $customer->name }}</p>
                                <p class="text-sm text-blue-700">{{ $customer->phone }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">Current Balance</h3>
                                <p class="text-lg font-bold text-red-600">{{ $currencySymbol }} {{ number_format($customer->balance, 2) }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">Credit Limit</h3>
                                <p class="text-lg font-semibold text-blue-900">{{ $currencySymbol }} {{ number_format($customer->credit_limit, 2) }}</p>
                                @php
                                    $percentage = $customer->credit_limit > 0 
                                        ? round(($customer->balance / $customer->credit_limit) * 100) 
                                        : 100;
                                @endphp
                                <div class="flex items-center mt-1">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full 
                                            {{ $percentage < 50 ? 'bg-green-600' : ($percentage < 80 ? 'bg-yellow-500' : 'bg-red-600') }}" 
                                            style="width: {{ min($percentage, 100) }}%;"></div>
                                    </div>
                                    <span class="ml-2 text-xs {{ $percentage < 50 ? 'text-green-700' : ($percentage < 80 ? 'text-yellow-700' : 'text-red-700') }}">
                                        {{ $percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Form -->
                    <div class="bg-gray-50 p-6 rounded-md">
                        <form action="{{ route('pos.credits.payment.store', $customer) }}" method="POST">
                            @csrf
                            
                            @if(session('error'))
                                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                    <strong>Error!</strong> {{ session('error') }}
                                </div>
                            @endif
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Payment Amount ({{ $currencySymbol }})</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">{{ $currencySymbol }}</span>
                                        </div>
                                        <input type="number" name="amount" id="amount" 
                                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-8 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                               placeholder="0.00" 
                                               step="0.01"
                                               min="0.01" 
                                               max="{{ $customer->balance }}" 
                                               value="{{ old('amount', $customer->balance) }}"
                                               required>
                                    </div>
                                    <div class="mt-1 flex justify-between text-xs text-gray-500">
                                        <span>Min: {{ $currencySymbol }} 0.01</span>
                                        <span>Max: {{ $currencySymbol }} {{ number_format($customer->balance, 2) }}</span>
                                    </div>
                                    @error('amount')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                    <select id="payment_method" name="payment_method" 
                                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            required>
                                        <option value="">Select Method</option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="mobile_money" {{ old('payment_method') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                    </select>
                                    @error('payment_method')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-1">Reference Number (Optional)</label>
                                <input type="text" name="reference_number" id="reference_number" 
                                       class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                       placeholder="Transaction ID, Check Number, etc."
                                       value="{{ old('reference_number') }}">
                                @error('reference_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="mb-6">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="3" 
                                          class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                          placeholder="Add any additional information about this payment">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="bg-gray-100 px-4 py-3 rounded-md mb-6">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Payment Preview</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="text-gray-600">Before Payment:</div>
                                    <div class="font-medium text-red-600">{{ $currencySymbol }} {{ number_format($customer->balance, 2) }}</div>
                                    
                                    <div class="text-gray-600">After Payment:</div>
                                    <div id="after-payment" class="font-medium text-green-600">{{ $currencySymbol }} {{ number_format(0, 2) }}</div>
                                    
                                    <div class="text-gray-600">Payment Status:</div>
                                    <div id="payment-status" class="font-medium text-green-600">Fully Paid</div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" onclick="window.history.back()" class="mr-3 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Record Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Live calculation of remaining balance
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amount');
            const afterPaymentEl = document.getElementById('after-payment');
            const paymentStatusEl = document.getElementById('payment-status');
            const currentBalance = {{ $customer->balance }};
            const currencySymbol = '{{ $currencySymbol }}';
            
            function updatePreview() {
                const paymentAmount = parseFloat(amountInput.value) || 0;
                const remainingBalance = Math.max(0, currentBalance - paymentAmount).toFixed(2);
                
                afterPaymentEl.textContent = currencySymbol + ' ' + remainingBalance;
                
                if (remainingBalance <= 0) {
                    paymentStatusEl.textContent = 'Fully Paid';
                    paymentStatusEl.className = 'font-medium text-green-600';
                } else {
                    paymentStatusEl.textContent = 'Partially Paid';
                    paymentStatusEl.className = 'font-medium text-yellow-600';
                }
            }
            
            amountInput.addEventListener('input', updatePreview);
            updatePreview(); // Initialize with default value
        });
    </script>
</x-app-layout>