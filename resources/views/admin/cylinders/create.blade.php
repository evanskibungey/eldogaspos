<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">New Cylinder Transaction</h1>
                                <p class="text-gray-600 text-sm">Record a new cylinder drop-off or advance collection</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('admin.cylinders.index') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <form method="POST" action="{{ route('admin.cylinders.store') }}" class="space-y-0">
                    @csrf
                    
                    <!-- Progress Indicator -->
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
                        <div class="flex items-center justify-between text-white">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-semibold">1</span>
                                    </div>
                                    <span class="text-sm font-medium">Transaction Details</span>
                                </div>
                                <div class="w-12 h-0.5 bg-white bg-opacity-30"></div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-semibold">2</span>
                                    </div>
                                    <span class="text-sm font-medium">Customer & Payment</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 space-y-10">
                        <!-- Transaction Type Selection -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Transaction Type</h3>
                                    <p class="text-sm text-gray-600">Choose the type of cylinder transaction</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="transaction-type-card cursor-pointer">
                                    <input type="radio" name="transaction_type" value="drop_off" 
                                           class="sr-only peer" 
                                           {{ old('transaction_type', 'drop_off') === 'drop_off' ? 'checked' : '' }}>
                                    <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-all duration-200 hover:border-orange-300 hover:shadow-md">
                                        <div class="flex items-start space-x-4">
                                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900 mb-2">Drop-off First</h4>
                                                <p class="text-sm text-gray-600 leading-relaxed">Customer leaves empty cylinder and will collect refilled one later</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label class="transaction-type-card cursor-pointer">
                                    <input type="radio" name="transaction_type" value="advance_collection" 
                                           class="sr-only peer"
                                           {{ old('transaction_type') === 'advance_collection' ? 'checked' : '' }}>
                                    <div class="p-6 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-all duration-200 hover:border-orange-300 hover:shadow-md">
                                        <div class="flex items-start space-x-4">
                                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-gray-900 mb-2">Advance Collection</h4>
                                                <p class="text-sm text-gray-600 leading-relaxed">Customer takes refilled cylinder now, will return empty cylinder later</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @error('transaction_type')
                                <div class="flex items-center gap-2 text-red-600 text-sm">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Customer Information -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Customer Information</h3>
                                    <p class="text-sm text-gray-600">Select existing customer or add new one</p>
                                </div>
                            </div>

                            <!-- Customer Mode Toggle -->
                            <div class="flex flex-wrap gap-3">
                                <label class="customer-mode-toggle cursor-pointer">
                                    <input type="radio" name="customer_mode" value="existing" class="sr-only peer" checked>
                                    <div class="px-6 py-3 bg-white border-2 border-gray-200 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition-all duration-200 hover:border-orange-300">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v1a2 2 0 002 2h2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v1a2 2 0 01-2 2H9m0 0v1a2 2 0 002 2h2a2 2 0 002-2v-1"/>
                                            </svg>
                                            <span class="font-medium">Select Existing Customer</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="customer-mode-toggle cursor-pointer">
                                    <input type="radio" name="customer_mode" value="new" class="sr-only peer">
                                    <div class="px-6 py-3 bg-white border-2 border-gray-200 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700 transition-all duration-200 hover:border-orange-300">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            <span class="font-medium">Add New Customer</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Existing Customer Selection -->
                            <div id="existing_customer_section" class="space-y-4">
                                <div class="relative">
                                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
                                    <div class="relative">
                                        <select name="customer_id" id="customer_id"
                                                class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white appearance-none">
                                            <option value="">Choose a customer...</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }} - {{ $customer->phone }}
                                                    @if($customer->balance > 0)
                                                        (Balance: KSh {{ number_format($customer->balance, 0) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    @error('customer_id')
                                        <div class="flex items-center gap-2 text-red-600 text-sm mt-2">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- New Customer Form -->
                            <div id="new_customer_section" style="display: none;" class="bg-gray-50 rounded-xl p-6 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="customer_name" class="block text-sm font-medium text-gray-700">Customer Name</label>
                                        <input type="text" name="customer_name" id="customer_name" 
                                               value="{{ old('customer_name') }}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                               placeholder="Enter customer name">
                                        @error('customer_name')
                                            <div class="flex items-center gap-2 text-red-600 text-sm">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label for="customer_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                        <input type="text" name="customer_phone" id="customer_phone" 
                                               value="{{ old('customer_phone') }}"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                               placeholder="Enter phone number">
                                        @error('customer_phone')
                                            <div class="flex items-center gap-2 text-red-600 text-sm">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cylinder Details -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Cylinder Details</h3>
                                    <p class="text-sm text-gray-600">Specify cylinder size and type</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="cylinder_size" class="block text-sm font-medium text-gray-700">Cylinder Size</label>
                                    <div class="relative">
                                        <select name="cylinder_size" id="cylinder_size" required
                                                class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white appearance-none">
                                            <option value="">Select size...</option>
                                            <option value="6kg" {{ old('cylinder_size') === '6kg' ? 'selected' : '' }}>6kg - Small Cylinder</option>
                                            <option value="13kg" {{ old('cylinder_size') === '13kg' ? 'selected' : '' }}>13kg - Standard Cylinder</option>
                                            <option value="50kg" {{ old('cylinder_size') === '50kg' ? 'selected' : '' }}>50kg - Large Cylinder</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    @error('cylinder_size')
                                        <div class="flex items-center gap-2 text-red-600 text-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="cylinder_type" class="block text-sm font-medium text-gray-700">Cylinder Type</label>
                                    <div class="relative">
                                        <select name="cylinder_type" id="cylinder_type" required
                                                class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white appearance-none">
                                            <option value="">Select type...</option>
                                            <option value="LPG" {{ old('cylinder_type', 'LPG') === 'LPG' ? 'selected' : '' }}>LPG (Cooking Gas)</option>
                                            <option value="Oxygen" {{ old('cylinder_type') === 'Oxygen' ? 'selected' : '' }}>Oxygen</option>
                                            <option value="Acetylene" {{ old('cylinder_type') === 'Acetylene' ? 'selected' : '' }}>Acetylene</option>
                                            <option value="Other" {{ old('cylinder_type') === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    @error('cylinder_type')
                                        <div class="flex items-center gap-2 text-red-600 text-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Payment Details</h3>
                                    <p class="text-sm text-gray-600">Enter refill amount and payment information</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="amount" class="block text-sm font-medium text-gray-700">Gas Refill Amount</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">KSh</span>
                                        </div>
                                        <input type="number" name="amount" id="amount" step="0.01" min="0" 
                                               value="{{ old('amount') }}" required
                                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                               placeholder="0.00">
                                    </div>
                                    @error('amount')
                                        <div class="flex items-center gap-2 text-red-600 text-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div id="deposit_section" style="display: none;" class="space-y-2">
                                    <label for="deposit_amount" class="block text-sm font-medium text-gray-700">Deposit Amount</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">KSh</span>
                                        </div>
                                        <input type="number" name="deposit_amount" id="deposit_amount" step="0.01" min="0" 
                                               value="{{ old('deposit_amount', 0) }}"
                                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                               placeholder="0.00">
                                    </div>
                                    <p class="text-xs text-gray-500">Extra amount collected for advance collection</p>
                                    @error('deposit_amount')
                                        <div class="flex items-center gap-2 text-red-600 text-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Payment Status -->
                            <div class="space-y-4">
                                <label class="block text-sm font-medium text-gray-700">Payment Status</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label class="payment-status-card cursor-pointer">
                                        <input type="radio" name="payment_status" value="paid" 
                                               class="sr-only peer"
                                               {{ old('payment_status') === 'paid' ? 'checked' : '' }}>
                                        <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-green-500 peer-checked:bg-green-50 transition-all duration-200 hover:border-green-300 hover:shadow-md">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-900">Paid</h4>
                                                    <p class="text-sm text-gray-600">Customer has paid for the refill</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="payment-status-card cursor-pointer">
                                        <input type="radio" name="payment_status" value="pending" 
                                               class="sr-only peer"
                                               {{ old('payment_status', 'pending') === 'pending' ? 'checked' : '' }}>
                                        <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-yellow-500 peer-checked:bg-yellow-50 transition-all duration-200 hover:border-yellow-300 hover:shadow-md">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-900">Pending</h4>
                                                    <p class="text-sm text-gray-600">Customer will pay later</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                @error('payment_status')
                                    <div class="flex items-center gap-2 text-red-600 text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Additional Notes</h3>
                                    <p class="text-sm text-gray-600">Add any additional information about this transaction</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <textarea name="notes" id="notes" rows="4" 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"
                                          placeholder="Enter any additional notes about this transaction...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="flex items-center gap-2 text-red-600 text-sm">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-between gap-4">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            All fields marked with * are required
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('admin.cylinders.index') }}" 
                               class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200 transform hover:scale-105">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Create Transaction
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Card (Optional Enhancement) -->
            <div id="transaction_summary" class="mt-6 bg-white rounded-xl shadow-lg border border-gray-100 p-6" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="font-medium text-blue-800">Transaction Type</div>
                        <div id="summary_type" class="text-blue-600 mt-1">-</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="font-medium text-green-800">Cylinder</div>
                        <div id="summary_cylinder" class="text-green-600 mt-1">-</div>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                        <div class="font-medium text-orange-800">Total Amount</div>
                        <div id="summary_amount" class="text-orange-600 mt-1">KSh 0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom animations and transitions */
        .transaction-type-card:hover .w-12 {
            transform: scale(1.1);
            transition: transform 0.2s ease-in-out;
        }
        
        .customer-mode-toggle:hover > div {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .payment-status-card:hover .w-10 {
            transform: scale(1.1);
            transition: transform 0.2s ease-in-out;
        }
        
        /* Focus styles for better accessibility */
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        /* Loading state for submit button */
        .form-submitting {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .form-submitting .submit-btn {
            background: linear-gradient(45deg, #9ca3af, #6b7280);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const transactionTypeRadios = document.querySelectorAll('input[name="transaction_type"]');
            const customerModeRadios = document.querySelectorAll('input[name="customer_mode"]');
            const depositSection = document.getElementById('deposit_section');
            const existingCustomerSection = document.getElementById('existing_customer_section');
            const newCustomerSection = document.getElementById('new_customer_section');
            const customerIdSelect = document.getElementById('customer_id');
            const customerNameInput = document.getElementById('customer_name');
            const customerPhoneInput = document.getElementById('customer_phone');
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type="submit"]');
            
            // Summary elements
            const summaryCard = document.getElementById('transaction_summary');
            const summaryType = document.getElementById('summary_type');
            const summaryCylinder = document.getElementById('summary_cylinder');
            const summaryAmount = document.getElementById('summary_amount');
            
            // Toggle deposit section based on transaction type
            function toggleDepositSection() {
                const selectedType = document.querySelector('input[name="transaction_type"]:checked')?.value;
                if (selectedType === 'advance_collection') {
                    depositSection.style.display = 'block';
                    depositSection.classList.add('animate-fadeIn');
                } else {
                    depositSection.style.display = 'none';
                    document.getElementById('deposit_amount').value = '0';
                }
                updateSummary();
            }
            
            // Toggle customer input sections
            function toggleCustomerSections() {
                const selectedMode = document.querySelector('input[name="customer_mode"]:checked')?.value;
                if (selectedMode === 'new') {
                    existingCustomerSection.style.display = 'none';
                    newCustomerSection.style.display = 'block';
                    newCustomerSection.classList.add('animate-fadeIn');
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
            
            // Update transaction summary
            function updateSummary() {
                const transactionType = document.querySelector('input[name="transaction_type"]:checked')?.value;
                const cylinderSize = document.getElementById('cylinder_size').value;
                const cylinderType = document.getElementById('cylinder_type').value;
                const amount = parseFloat(document.getElementById('amount').value) || 0;
                const depositAmount = parseFloat(document.getElementById('deposit_amount').value) || 0;
                
                if (transactionType || cylinderSize || cylinderType || amount > 0) {
                    summaryCard.style.display = 'block';
                    summaryCard.classList.add('animate-fadeIn');
                    
                    // Update summary content
                    summaryType.textContent = transactionType ? 
                        (transactionType === 'drop_off' ? 'Drop-off First' : 'Advance Collection') : '-';
                    
                    summaryCylinder.textContent = (cylinderSize && cylinderType) ? 
                        `${cylinderSize} ${cylinderType}` : '-';
                    
                    const totalAmount = amount + depositAmount;
                    summaryAmount.textContent = totalAmount > 0 ? `KSh ${totalAmount.toLocaleString()}` : 'KSh 0';
                }
            }
            
            // Form validation with visual feedback
            function validateForm() {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-300', 'bg-red-50');
                        field.classList.remove('border-gray-300');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-300', 'bg-red-50');
                        field.classList.add('border-gray-300');
                    }
                });
                
                return isValid;
            }
            
            // Enhanced form submission
            function handleFormSubmit(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    
                    // Smooth scroll to first error
                    const firstError = form.querySelector('.border-red-300');
                    if (firstError) {
                        firstError.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                        firstError.focus();
                    }
                    return;
                }
                
                // Show loading state
                form.classList.add('form-submitting');
                submitBtn.innerHTML = `
                    <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Creating Transaction...
                `;
                submitBtn.disabled = true;
            }
            
            // Event listeners
            transactionTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleDepositSection);
            });
            
            customerModeRadios.forEach(radio => {
                radio.addEventListener('change', toggleCustomerSections);
            });
            
            // Real-time summary updates
            ['cylinder_size', 'cylinder_type', 'amount', 'deposit_amount'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', updateSummary);
                    element.addEventListener('input', updateSummary);
                }
            });
            
            // Form submission handler
            form.addEventListener('submit', handleFormSubmit);
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.required && !this.value.trim()) {
                        this.classList.add('border-red-300', 'bg-red-50');
                    } else {
                        this.classList.remove('border-red-300', 'bg-red-50');
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('border-red-300') && this.value.trim()) {
                        this.classList.remove('border-red-300', 'bg-red-50');
                        this.classList.add('border-gray-300');
                    }
                });
            });
            
            // Initial setup
            toggleDepositSection();
            toggleCustomerSections();
            updateSummary();
            
            // Add fade-in animation class
            const style = document.createElement('style');
            style.textContent = `
                .animate-fadeIn {
                    animation: fadeIn 0.3s ease-in-out;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</x-app-layout>