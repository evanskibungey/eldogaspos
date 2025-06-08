<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">New Cylinder Transaction</h2>
                    <p class="text-sm text-gray-500 mt-1">Record a new cylinder drop-off or advance collection</p>
                </div>
                <a href="{{ route('pos.cylinders.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to List
                </a>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                <form method="POST" action="{{ route('pos.cylinders.store') }}">
                    @csrf
                    
                    <div class="p-6 space-y-6">
                        <!-- Transaction Type Selection -->
                        <div>
                            <label class="text-base font-medium text-gray-900">Transaction Type</label>
                            <p class="text-sm leading-5 text-gray-500">Choose the type of cylinder transaction</p>
                            <fieldset class="mt-4">
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input id="drop_off" name="transaction_type" type="radio" value="drop_off" 
                                               class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500" 
                                               {{ old('transaction_type', 'drop_off') === 'drop_off' ? 'checked' : '' }}>
                                        <label for="drop_off" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="font-semibold">Drop-off First</span>
                                            <p class="text-gray-500">Customer leaves empty cylinder and will collect refilled one later</p>
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="advance_collection" name="transaction_type" type="radio" value="advance_collection" 
                                               class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                               {{ old('transaction_type') === 'advance_collection' ? 'checked' : '' }}>
                                        <label for="advance_collection" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="font-semibold">Advance Collection</span>
                                            <p class="text-gray-500">Customer takes refilled cylinder now, will return empty cylinder later</p>
                                        </label>
                                    </div>
                                </div>
                            </fieldset>
                            @error('transaction_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Customer Selection/Entry -->
                        <div>
                            <label class="text-base font-medium text-gray-900">Customer Information</label>
                            <div class="mt-4 space-y-4">
                                <!-- Customer Selection Mode Toggle -->
                                <div class="flex space-x-4">
                                    <div class="flex items-center">
                                        <input id="existing_customer" name="customer_mode" type="radio" value="existing" 
                                               class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500" 
                                               checked>
                                        <label for="existing_customer" class="ml-2 block text-sm font-medium text-gray-700">
                                            Select Existing Customer
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="new_customer" name="customer_mode" type="radio" value="new" 
                                               class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500">
                                        <label for="new_customer" class="ml-2 block text-sm font-medium text-gray-700">
                                            Add New Customer
                                        </label>
                                    </div>
                                </div>

                                <!-- Existing Customer Selection -->
                                <div id="existing_customer_section">
                                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
                                    <select name="customer_id" id="customer_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                        <option value="">Select a customer...</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} - {{ $customer->phone }}
                                                @if($customer->balance > 0)
                                                    (Balance: KSh {{ number_format($customer->balance, 0) }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- New Customer Form -->
                                <div id="new_customer_section" style="display: none;">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                                            <input type="text" name="customer_name" id="customer_name" 
                                                   value="{{ old('customer_name') }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                            @error('customer_name')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                            <input type="text" name="customer_phone" id="customer_phone" 
                                                   value="{{ old('customer_phone') }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                            @error('customer_phone')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cylinder Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="cylinder_size" class="block text-sm font-medium text-gray-700 mb-2">Cylinder Size</label>
                                <select name="cylinder_size" id="cylinder_size" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Select size...</option>
                                    <option value="6kg" {{ old('cylinder_size') === '6kg' ? 'selected' : '' }}>6kg</option>
                                    <option value="13kg" {{ old('cylinder_size') === '13kg' ? 'selected' : '' }}>13kg</option>
                                    <option value="50kg" {{ old('cylinder_size') === '50kg' ? 'selected' : '' }}>50kg</option>
                                </select>
                                @error('cylinder_size')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="cylinder_type" class="block text-sm font-medium text-gray-700 mb-2">Cylinder Type</label>
                                <select name="cylinder_type" id="cylinder_type" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Select type...</option>
                                    <option value="LPG" {{ old('cylinder_type', 'LPG') === 'LPG' ? 'selected' : '' }}>LPG (Cooking Gas)</option>
                                    <option value="Oxygen" {{ old('cylinder_type') === 'Oxygen' ? 'selected' : '' }}>Oxygen</option>
                                    <option value="Acetylene" {{ old('cylinder_type') === 'Acetylene' ? 'selected' : '' }}>Acetylene</option>
                                    <option value="Other" {{ old('cylinder_type') === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('cylinder_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Gas Refill Amount (KSh)</label>
                                <input type="number" name="amount" id="amount" step="0.01" min="0" 
                                       value="{{ old('amount') }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="deposit_section" style="display: none;">
                                <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-2">Deposit Amount (KSh)</label>
                                <input type="number" name="deposit_amount" id="deposit_amount" step="0.01" min="0" 
                                       value="{{ old('deposit_amount', 0) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <p class="mt-1 text-xs text-gray-500">Extra amount collected for advance collection</p>
                                @error('deposit_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Status -->
                        <div>
                            <label class="text-sm font-medium text-gray-700">Payment Status</label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center">
                                    <input id="paid" name="payment_status" type="radio" value="paid" 
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                           {{ old('payment_status') === 'paid' ? 'checked' : '' }}>
                                    <label for="paid" class="ml-2 block text-sm text-gray-900">
                                        Paid - Customer has paid for the refill
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="pending" name="payment_status" type="radio" value="pending" 
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                           {{ old('payment_status', 'pending') === 'pending' ? 'checked' : '' }}>
                                    <label for="pending" class="ml-2 block text-sm text-gray-900">
                                        Pending - Customer will pay later
                                    </label>
                                </div>
                            </div>
                            @error('payment_status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                      placeholder="Any additional notes about this transaction...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                        <a href="{{ route('pos.cylinders.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                            Create Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transactionTypeRadios = document.querySelectorAll('input[name="transaction_type"]');
            const customerModeRadios = document.querySelectorAll('input[name="customer_mode"]');
            const depositSection = document.getElementById('deposit_section');
            const existingCustomerSection = document.getElementById('existing_customer_section');
            const newCustomerSection = document.getElementById('new_customer_section');
            const customerIdSelect = document.getElementById('customer_id');
            const customerNameInput = document.getElementById('customer_name');
            const customerPhoneInput = document.getElementById('customer_phone');
            
            // Toggle deposit section based on transaction type
            function toggleDepositSection() {
                const selectedType = document.querySelector('input[name="transaction_type"]:checked')?.value;
                if (selectedType === 'advance_collection') {
                    depositSection.style.display = 'block';
                } else {
                    depositSection.style.display = 'none';
                    document.getElementById('deposit_amount').value = '0';
                }
            }
            
            // Toggle customer input sections
            function toggleCustomerSections() {
                const selectedMode = document.querySelector('input[name="customer_mode"]:checked')?.value;
                if (selectedMode === 'new') {
                    existingCustomerSection.style.display = 'none';
                    newCustomerSection.style.display = 'block';
                    customerIdSelect.required = false;
                    customerNameInput.required = true;
                    customerPhoneInput.required = true;
                } else {
                    existingCustomerSection.style.display = 'block';
                    newCustomerSection.style.display = 'none';
                    customerIdSelect.required = true;
                    customerNameInput.required = false;
                    customerPhoneInput.required = false;
                }
            }
            
            // Event listeners
            transactionTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleDepositSection);
            });
            
            customerModeRadios.forEach(radio => {
                radio.addEventListener('change', toggleCustomerSections);
            });
            
            // Initial checks
            toggleDepositSection();
            toggleCustomerSections();
        });
    </script>
</x-app-layout>