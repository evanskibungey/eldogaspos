<x-app-layout>
    <div class="py-6 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Edit Cylinder Transaction</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $cylinder->reference_number }}</p>
                </div>
                <a href="{{ route('admin.cylinders.show', $cylinder) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Details
                </a>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                <form method="POST" action="{{ route('admin.cylinders.update', $cylinder) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="p-6 space-y-6">
                        <!-- Transaction Type Display -->
                        <div>
                            <label class="text-base font-medium text-gray-900">Transaction Type</label>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium {{ $cylinder->getTransactionTypeBadgeColor() }}">
                                    {{ $cylinder->isDropOff() ? 'Drop-off First' : 'Advance Collection' }}
                                </span>
                                <p class="mt-1 text-sm text-gray-500">Transaction type cannot be changed after creation</p>
                            </div>
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                            <select name="customer_id" id="customer_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select a customer...</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $cylinder->customer_id == $customer->id ? 'selected' : '' }}>
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

                        <!-- Cylinder Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="cylinder_size" class="block text-sm font-medium text-gray-700 mb-2">Cylinder Size</label>
                                <select name="cylinder_size" id="cylinder_size" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <option value="">Select size...</option>
                                    <option value="6kg" {{ $cylinder->cylinder_size === '6kg' ? 'selected' : '' }}>6kg</option>
                                    <option value="13kg" {{ $cylinder->cylinder_size === '13kg' ? 'selected' : '' }}>13kg</option>
                                    <option value="50kg" {{ $cylinder->cylinder_size === '50kg' ? 'selected' : '' }}>50kg</option>
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
                                    <option value="LPG" {{ $cylinder->cylinder_type === 'LPG' ? 'selected' : '' }}>LPG (Cooking Gas)</option>
                                    <option value="Oxygen" {{ $cylinder->cylinder_type === 'Oxygen' ? 'selected' : '' }}>Oxygen</option>
                                    <option value="Acetylene" {{ $cylinder->cylinder_type === 'Acetylene' ? 'selected' : '' }}>Acetylene</option>
                                    <option value="Other" {{ $cylinder->cylinder_type === 'Other' ? 'selected' : '' }}>Other</option>
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
                                       value="{{ old('amount', $cylinder->amount) }}" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="deposit_section" {{ $cylinder->isDropOff() ? 'style=display:none;' : '' }}>
                                <label for="deposit_amount" class="block text-sm font-medium text-gray-700 mb-2">Deposit Amount (KSh)</label>
                                <input type="number" name="deposit_amount" id="deposit_amount" step="0.01" min="0" 
                                       value="{{ old('deposit_amount', $cylinder->deposit_amount) }}"
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
                                           {{ $cylinder->payment_status === 'paid' ? 'checked' : '' }}>
                                    <label for="paid" class="ml-2 block text-sm text-gray-900">
                                        Paid - Customer has paid for the refill
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="pending" name="payment_status" type="radio" value="pending" 
                                           class="h-4 w-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                           {{ $cylinder->payment_status === 'pending' ? 'checked' : '' }}>
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
                                      placeholder="Any additional notes about this transaction...">{{ old('notes', $cylinder->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                        <a href="{{ route('admin.cylinders.show', $cylinder) }}" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                            Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>