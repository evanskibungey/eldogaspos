@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';
    $storeRoute = $isPosContext ? 'pos.cylinders.store' : 'admin.cylinders.store';
    $quickCreateUrl = $isPosContext ? route('pos.customers.quick-store') : route('admin.customers.quick-store');
@endphp

<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Enhanced Header with Icon -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center justify-center w-14 h-14 bg-gradient-to-br from-orange-500 to-pink-600 rounded-2xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">New Cylinder Transaction</h1>
                            <p class="text-gray-600 mt-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Select products, specify brands, and manage customer details
                            </p>
                        </div>
                    </div>
                    <a href="{{ route($indexRoute) }}" class="inline-flex items-center px-6 py-3 bg-white border-2 border-gray-200 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>

            @if($products->isEmpty())
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 p-6 rounded-xl shadow-md">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-yellow-900 text-lg">No products available</p>
                            <p class="text-yellow-700 text-sm mt-1">There are currently no products with available stock. Please add inventory first.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Transaction Type & Products -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Transaction Type Card with Enhanced Design -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-orange-500 px-6 py-4">
                                <h3 class="font-bold text-lg text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    Transaction Type
                                    <span class="ml-2 text-xs bg-white/20 px-2 py-1 rounded-full">Required</span>
                                </h3>
                                <p class="text-orange-100 text-sm mt-1">Choose how the transaction will be handled</p>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="transaction_type" value="drop_off" class="sr-only peer" checked>
                                        <div class="p-5 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300 transition-all duration-200 peer-checked:shadow-lg">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0 w-10 h-10 bg-gray-100 peer-checked:bg-orange-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                                    <svg class="w-6 h-6 text-gray-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="font-bold text-gray-900">Drop-off First</p>
                                                    <p class="text-sm text-gray-600 mt-1">Customer brings empty cylinder, collects refilled one later</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="transaction_type" value="advance_collection" class="sr-only peer">
                                        <div class="p-5 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 hover:border-orange-300 transition-all duration-200 peer-checked:shadow-lg">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0 w-10 h-10 bg-gray-100 peer-checked:bg-orange-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                                    <svg class="w-6 h-6 text-gray-600 peer-checked:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m0 0l-7 7-7-7z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="font-bold text-gray-900">Advance Collection</p>
                                                    <p class="text-sm text-gray-600 mt-1">Customer takes gas now, returns empty cylinder later</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Available Products Card with Enhanced Design -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-gray-900 px-6 py-4">
                                <h3 class="font-bold text-lg text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Available Products
                                    <span class="ml-2 text-xs bg-white/20 px-2 py-1 rounded-full">{{ count($products) }} items</span>
                                </h3>
                                <p class="text-gray-300 text-sm mt-1">Click on a product to add it to your cart</p>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[600px] overflow-y-auto pr-2">
                                    @foreach($products as $product)
                                        <div class="group border-2 border-gray-200 rounded-xl p-4 hover:border-orange-400 hover:shadow-xl transition-all duration-200 cursor-pointer product-card bg-white hover:bg-orange-50"
                                             data-id="{{ $product->id }}"
                                             data-name="{{ $product->name }}"
                                             data-price="{{ $product->price }}"
                                             data-stock="{{ $product->stock }}">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex-1">
                                                    <p class="font-bold text-gray-900 group-hover:text-orange-900 transition-colors">{{ $product->name }}</p>
                                                    <p class="text-xs text-gray-500 mt-1 flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                        </svg>
                                                        {{ $product->category->name ?? 'Uncategorized' }}
                                                    </p>
                                                </div>
                                                <div class="flex-shrink-0 w-10 h-10 bg-orange-100 group-hover:bg-orange-500 rounded-lg flex items-center justify-center transition-all duration-200 group-hover:scale-110">
                                                    <svg class="w-6 h-6 text-orange-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                                <div>
                                                    <p class="text-xs text-gray-500 mb-1">Price</p>
                                                    <span class="text-lg font-bold text-orange-600">KSh {{ number_format($product->price, 0) }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs text-gray-500 mb-1">Stock</p>
                                                    <span class="inline-flex items-center text-xs px-3 py-1.5 rounded-full font-semibold {{ $product->stock > $product->min_stock ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                        </svg>
                                                        {{ $product->stock }} units
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Customer & Cart -->
                    <div class="space-y-6">
                        <!-- Customer Selection Card with Enhanced Design -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden sticky top-6">
                            <div class="bg-gray-900 px-6 py-4">
                                <h3 class="font-bold text-lg text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Customer
                                    <span class="ml-2 text-xs bg-white/20 px-2 py-1 rounded-full">Required</span>
                                </h3>
                                <p class="text-gray-300 text-sm mt-1">Search or add a new customer</p>
                            </div>
                            <div class="p-6">
                                <div class="relative">
                                    <input type="text" id="customer_search" class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all" placeholder="Search customer by name or phone...">
                                    <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input type="hidden" id="customer_id">
                                <input type="hidden" id="customer_name">
                                <input type="hidden" id="customer_phone">

                                <div id="customer_dropdown" class="hidden mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-xl max-h-64 overflow-y-auto">
                                    <div class="customer-option px-4 py-3 hover:bg-green-50 cursor-pointer border-b-2 border-green-200 bg-green-50"
                                         data-id="new" data-search="new add customer create">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-sm text-green-900">Add New Customer</p>
                                                <p class="text-xs text-green-700">Create a new customer record</p>
                                            </div>
                                        </div>
                                    </div>
                                    @foreach($customers as $customer)
                                        <div class="customer-option px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                             data-id="{{ $customer->id }}"
                                             data-name="{{ $customer->name }}"
                                             data-phone="{{ $customer->phone }}"
                                             data-search="{{ strtolower($customer->name . ' ' . $customer->phone) }}">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-sm text-gray-900">{{ $customer->name }}</p>
                                                    <p class="text-xs text-gray-600">{{ $customer->phone }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div id="new_customer_form" class="hidden mt-4 space-y-3 p-4 bg-green-50 rounded-xl border-2 border-green-200">
                                    <div class="flex items-center mb-2">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        <p class="font-semibold text-green-900">New Customer Details</p>
                                    </div>
                                    <input type="text" id="new_name" placeholder="Full Name *" class="w-full px-4 py-3 border-2 border-green-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <input type="text" id="new_phone" placeholder="Phone Number *" class="w-full px-4 py-3 border-2 border-green-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                    <button type="button" id="create_customer_btn" class="w-full px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 font-semibold shadow-lg hover:shadow-xl transition-all duration-200">
                                        Create & Select Customer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Shopping Cart Card with Enhanced Design -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="bg-orange-500 px-6 py-4">
                                <h3 class="font-bold text-lg text-white flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Shopping Cart
                                </h3>
                                <p class="text-orange-100 text-sm mt-1">Review your selected items</p>
                            </div>
                            <div class="p-6">
                                <div id="cart-items" class="space-y-3 mb-4 min-h-[120px] max-h-[300px] overflow-y-auto">
                                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                        <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <p class="text-sm font-medium">Your cart is empty</p>
                                        <p class="text-xs mt-1">Add products to get started</p>
                                    </div>
                                </div>

                                <div class="border-t-2 border-gray-100 pt-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Subtotal:</span>
                                        <span id="subtotal" class="font-bold text-gray-900">KSh 0</span>
                                    </div>
                                    <div id="deposit-wrapper" class="hidden p-4 bg-red-50 rounded-xl border-2 border-red-200">
                                        <label class="flex items-center text-sm font-semibold text-red-700 mb-2">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Deposit Amount (Required)
                                        </label>
                                        <input type="number" id="deposit" value="0" min="0" placeholder="Enter deposit amount" class="w-full px-4 py-3 border-2 border-red-300 rounded-xl text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                    </div>
                                    <div class="flex justify-between items-center text-lg font-bold border-t-2 border-gray-200 pt-3">
                                        <span class="text-gray-900">Total:</span>
                                        <span id="total" class="text-2xl text-orange-600">KSh 0</span>
                                    </div>
                                </div>

                                <div class="mt-6 p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                                    <p class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Payment Status
                                    </p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="payment" value="paid" class="sr-only peer">
                                            <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-green-500 peer-checked:bg-green-50 text-center text-sm font-semibold hover:border-green-300 transition-all peer-checked:shadow-md">
                                                <svg class="w-5 h-5 mx-auto mb-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Paid
                                            </div>
                                        </label>
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="payment" value="pending" class="sr-only peer" checked>
                                            <div class="p-3 border-2 border-gray-200 rounded-xl peer-checked:border-orange-500 peer-checked:bg-orange-50 text-center text-sm font-semibold hover:border-orange-300 transition-all peer-checked:shadow-md">
                                                <svg class="w-5 h-5 mx-auto mb-1 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Pending
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <textarea id="notes" rows="3" placeholder="Add notes (optional)..." class="w-full mt-4 px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"></textarea>

                                <button type="button" id="submit-btn" class="w-full mt-4 px-6 py-4 bg-orange-600 text-white font-bold rounded-xl hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-xl hover:shadow-2xl transition-all duration-200 transform hover:scale-[1.02]">
                                    <span class="flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Create Transaction
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Enhanced Brand Modal with Landscape Orientation -->
    <div id="brand-modal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-2 sm:p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[95vw] lg:max-w-6xl max-h-[95vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-orange-500 px-4 sm:px-6 py-3 sm:py-4 flex-shrink-0">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-white flex items-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-7 lg:h-7 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="hidden sm:inline">Add Product to Cart</span>
                            <span class="sm:hidden">Add to Cart</span>
                        </h3>
                        <p class="text-orange-100 text-xs sm:text-sm mt-1 hidden sm:block">Configure quantity and brand details</p>
                    </div>
                    <button type="button" id="brand-modal-close" class="text-white hover:text-gray-200 hover:bg-white/10 p-2 rounded-lg transition-all">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Product Summary Card - Compact -->
            <div class="px-4 sm:px-6 py-3 sm:py-4 bg-orange-50 border-b-2 border-orange-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-base sm:text-lg lg:text-xl text-gray-900 truncate" id="modal-product-name"></p>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <p class="text-xs sm:text-sm text-orange-700 font-semibold flex items-center bg-white px-2 py-0.5 sm:px-3 sm:py-1 rounded-lg" id="modal-product-price">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </p>
                            <p class="text-xs sm:text-sm text-orange-700 font-semibold flex items-center bg-white px-2 py-0.5 sm:px-3 sm:py-1 rounded-lg" id="modal-product-stock">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content - Landscape Layout -->
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 p-4 sm:p-6">
                    <!-- Left Side: Quantity & Brands (Wider on Desktop) -->
                    <div class="lg:col-span-8 space-y-4">
                        <!-- Step 1: Quantity Selection - Horizontal Layout -->
                        <div class="bg-gray-50 rounded-xl p-3 sm:p-4 border-2 border-gray-200">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="flex items-center flex-shrink-0">
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-2">1</div>
                                    <label class="text-sm sm:text-base font-bold text-gray-900 flex items-center">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        Quantity
                                    </label>
                                </div>
                                <div class="flex items-center gap-2 sm:gap-3 flex-1 sm:ml-auto sm:max-w-xs">
                                    <button type="button" id="decrease-quantity" class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-white border-2 border-gray-300 rounded-xl hover:bg-orange-50 hover:border-orange-500 transition-all shadow-sm flex items-center justify-center group">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 group-hover:text-orange-600 group-hover:scale-110 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <input type="number" id="total-quantity" min="1" value="1" class="flex-1 text-center px-2 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl text-xl sm:text-2xl font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                                    <button type="button" id="increase-quantity" class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 bg-white border-2 border-gray-300 rounded-xl hover:bg-orange-50 hover:border-orange-500 transition-all shadow-sm flex items-center justify-center group">
                                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 group-hover:text-orange-600 group-hover:scale-110 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Quick Brand Selection - Multi-row Layout -->
                        <div class="bg-orange-50 rounded-xl p-3 sm:p-4 border-2 border-orange-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                <div class="flex items-center">
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 bg-orange-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-2">2</div>
                                    <label class="text-sm sm:text-base font-bold text-gray-900 flex items-center">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        <span class="hidden sm:inline">Quick Brand Selection</span>
                                        <span class="sm:hidden">Select Brand</span>
                                    </label>
                                </div>
                                <button type="button" id="auto-fill-brands" class="text-xs sm:text-sm px-3 py-1.5 sm:px-4 sm:py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 font-semibold transition-all shadow-md hover:shadow-lg flex items-center self-start sm:self-auto">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Fill All
                                </button>
                            </div>
                            <!-- Brand buttons in rows with multiple columns -->
                            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2">
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Total Gas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">K-Gas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Pro Gas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Afrigas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Hashi Gas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Sea Gas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Olibya</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Supa Gas</button>
                                <button type="button" class="quick-brand px-2 sm:px-3 py-2 sm:py-2.5 border-2 border-gray-200 bg-white rounded-lg hover:border-orange-500 hover:bg-orange-100 text-xs sm:text-sm font-semibold transition-all hover:shadow-lg hover:scale-105 active:scale-95 whitespace-nowrap">Other</button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Individual Brand Assignment -->
                    <div class="lg:col-span-4">
                        <div class="bg-green-50 rounded-xl p-3 sm:p-4 border-2 border-green-200 h-full flex flex-col">
                            <div class="flex items-center mb-3 flex-shrink-0">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-2">3</div>
                                <label class="text-sm sm:text-base font-bold text-gray-900 flex items-center">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                    <span class="hidden sm:inline">Assign to Units</span>
                                    <span class="sm:hidden">Units</span>
                                </label>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-600 mb-3 hidden sm:block">Specify brand for each unit</p>
                            <div id="brand-inputs-container" class="space-y-2 flex-1 overflow-y-auto pr-1 sm:pr-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer - Compact -->
            <div class="flex gap-2 sm:gap-4 px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 border-t-2 border-gray-200 flex-shrink-0">
                <button type="button" id="brand-cancel" class="flex-1 px-3 sm:px-6 py-2.5 sm:py-3 border-2 border-gray-300 rounded-xl hover:bg-white hover:border-gray-400 font-bold text-sm sm:text-base text-gray-700 transition-all shadow-sm hover:shadow-md flex items-center justify-center">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel
                </button>
                <button type="button" id="brand-confirm" class="flex-1 px-3 sm:px-6 py-2.5 sm:py-3 bg-orange-600 text-white rounded-xl hover:bg-orange-700 font-bold text-sm sm:text-base shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02] flex items-center justify-center">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add to Cart
                </button>
            </div>
        </div>
    </div>

    @include('admin.cylinders.cart-script')
</x-app-layout>
