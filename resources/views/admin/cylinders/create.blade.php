@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';
    $storeRoute = $isPosContext ? 'pos.cylinders.store' : 'admin.cylinders.store';
@endphp

<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Enhanced Header Section -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="w-12 h-12 bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">New Cylinder Transaction</h1>
                                <p class="text-gray-600 text-sm mt-1">Create a new cylinder drop-off or advance collection record</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route($indexRoute) }}" 
                       class="inline-flex items-center justify-center px-6 py-3.5 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-white hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md group">
                        <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to List
                    </a>
                </div>
            </div>

            <!-- Enhanced Form Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl border border-gray-100/50 overflow-hidden">
                <form method="POST" action="{{ route($storeRoute) }}" class="space-y-0" id="cylinder-form">
                    @csrf
                    
                    <!-- Enhanced Progress Indicator -->
                    <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 px-8 py-6">
                        <div class="flex items-center justify-between text-white">
                            <div class="flex items-center space-x-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/30">
                                        <span class="text-sm font-bold">1</span>
                                    </div>
                                    <span class="text-sm font-semibold">Transaction Details</span>
                                </div>
                                <div class="w-16 h-0.5 bg-white/30 rounded-full"></div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/30">
                                        <span class="text-sm font-bold">2</span>
                                    </div>
                                    <span class="text-sm font-semibold">Customer & Payment</span>
                                </div>
                            </div>
                            <div class="hidden sm:flex items-center space-x-2 text-white/80">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs font-medium">All fields with * are required</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 lg:p-10 space-y-12">
                        <!-- Enhanced Transaction Type Selection -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-orange-100 to-red-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Transaction Type *</h3>
                                    <p class="text-sm text-gray-600 mt-1">Choose the type of cylinder transaction</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <label class="transaction-type-card group cursor-pointer">
                                    <input type="radio" name="transaction_type" value="drop_off" 
                                           class="sr-only peer" 
                                           {{ old('transaction_type', 'drop_off') === 'drop_off' ? 'checked' : '' }}>
                                    <div class="relative p-6 border-2 border-gray-200 rounded-2xl peer-checked:border-orange-500 peer-checked:bg-gradient-to-br peer-checked:from-orange-50 peer-checked:to-red-50 transition-all duration-300 hover:border-orange-300 hover:shadow-lg group-hover:scale-105">
                                        <div class="flex items-start space-x-4">
                                            <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-bold text-gray-900 mb-2 text-lg">Drop-off First</h4>
                                                <p class="text-sm text-gray-600 leading-relaxed">Customer leaves empty cylinder and will collect refilled one later</p>
                                                <div class="mt-3 flex items-center text-xs text-blue-600 font-medium">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Most common option
                                                </div>
                                            </div>
                                        </div>
                                        <div class="absolute top-4 right-4 w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-orange-500 peer-checked:bg-orange-500 flex items-center justify-center transition-all">
                                            <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                </label>

                                <label class="transaction-type-card group cursor-pointer">
                                    <input type="radio" name="transaction_type" value="advance_collection" 
                                           class="sr-only peer"
                                           {{ old('transaction_type') === 'advance_collection' ? 'checked' : '' }}>
                                    <div class="relative p-6 border-2 border-gray-200 rounded-2xl peer-checked:border-orange-500 peer-checked:bg-gradient-to-br peer-checked:from-orange-50 peer-checked:to-red-50 transition-all duration-300 hover:border-orange-300 hover:shadow-lg group-hover:scale-105">
                                        <div class="flex items-start space-x-4">
                                            <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-bold text-gray-900 mb-2 text-lg">Advance Collection</h4>
                                                <p class="text-sm text-gray-600 leading-relaxed">Customer takes refilled cylinder now, will return empty cylinder later</p>
                                                <div class="mt-3 flex items-center text-xs text-green-600 font-medium">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Requires deposit
                                                </div>
                                            </div>
                                        </div>
                                        <div class="absolute top-4 right-4 w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-orange-500 peer-checked:bg-orange-500 flex items-center justify-center transition-all">
                                            <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @error('transaction_type')
                                <div class="flex items-center gap-2 text-red-600 text-sm bg-red-50 p-3 rounded-lg border border-red-200">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Enhanced Customer Information with Add New Customer Button -->
                        <div class="space-y-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-gradient-to-r from-blue-100 to-cyan-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900">Customer Information *</h3>
                                        <p class="text-sm text-gray-600 mt-1">Search and select existing customer or add new one</p>
                                    </div>
                                </div>
                                
                                <!-- Add New Customer Button -->
                                <button type="button" id="add_new_customer_btn" 
                                        class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 border border-transparent rounded-xl text-sm font-semibold text-white hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-sm hover:shadow-md group">
                                    <svg class="w-4 h-4 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add New Customer
                                </button>
                            </div>

                            <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-2xl p-6 space-y-6">
                                <!-- Customer Selection Dropdown -->
                                <div class="space-y-2">
                                    <label for="customer_search" class="block text-sm font-semibold text-gray-700">Select Customer *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                        <input type="text" id="customer_search" 
                                               class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white/80 backdrop-blur-sm transition-all font-medium"
                                               placeholder="Search customer by name or phone..."
                                               autocomplete="off">
                                        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
                                        <input type="hidden" name="customer_name" id="customer_name_hidden" value="{{ old('customer_name') }}">
                                        <input type="hidden" name="customer_phone" id="customer_phone_hidden" value="{{ old('customer_phone') }}">
                                    </div>
                                    
                                    <!-- Customer Dropdown -->
                                    <div id="customer_dropdown" class="hidden absolute z-40 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-200 max-h-64 overflow-y-auto">
                                        <div class="p-2 space-y-1" id="customer_options">
                                            @foreach($customers as $customer)
                                                <div class="customer-option px-3 py-3 hover:bg-blue-50 cursor-pointer rounded-lg transition-colors border-b border-gray-100 last:border-b-0" 
                                                     data-customer-id="{{ $customer->id }}"
                                                     data-customer-name="{{ $customer->name }}"
                                                     data-customer-phone="{{ $customer->phone }}"
                                                     data-customer-balance="{{ $customer->balance }}"
                                                     data-search-text="{{ strtolower($customer->name . ' ' . $customer->phone) }}">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <p class="font-semibold text-gray-900">{{ $customer->name }}</p>
                                                            <p class="text-sm text-gray-600">{{ $customer->phone }}</p>
                                                            @if($customer->balance > 0)
                                                                <p class="text-xs text-red-600 font-medium">Balance: KSh {{ number_format($customer->balance, 0) }}</p>
                                                            @else
                                                                <p class="text-xs text-green-600 font-medium">No outstanding balance</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            <!-- Add New Customer Option -->
                                            <div class="customer-option px-3 py-3 hover:bg-green-50 cursor-pointer rounded-lg transition-colors border-t-2 border-green-200 mt-2" 
                                                 data-customer-id="new"
                                                 data-customer-name=""
                                                 data-customer-phone=""
                                                 data-customer-balance="0"
                                                 data-search-text="new customer add create">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-green-900">+ Add New Customer</p>
                                                        <p class="text-sm text-green-600">Create a new customer record</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @error('customer_id')
                                        <div class="flex items-center gap-2 text-red-600 text-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- New Customer Form (Initially Hidden) -->
                                <div id="new_customer_form" class="hidden space-y-4 bg-white/80 backdrop-blur-sm rounded-xl p-6 border-2 border-green-200">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-bold text-gray-800 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            New Customer Details
                                        </h4>
                                        <button type="button" id="cancel_new_customer" class="text-gray-400 hover:text-gray-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label for="customer_name" class="block text-sm font-semibold text-gray-700">Customer Name *</label>
                                            <input type="text" name="customer_name" id="customer_name" 
                                                   value="{{ old('customer_name') }}"
                                                   class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white/80 backdrop-blur-sm transition-all"
                                                   placeholder="Enter full name">
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
                                            <label for="customer_phone" class="block text-sm font-semibold text-gray-700">Phone Number *</label>
                                            <input type="text" name="customer_phone" id="customer_phone" 
                                                   value="{{ old('customer_phone') }}"
                                                   class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white/80 backdrop-blur-sm transition-all"
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
                        </div>

                        <!-- Enhanced Cylinder Details with Autocomplete -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-pink-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Cylinder Details *</h3>
                                    <p class="text-sm text-gray-600 mt-1">Specify cylinder size and type</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="cylinder_size" class="block text-sm font-semibold text-gray-700">Cylinder Size *</label>
                                    <div class="relative">
                                        <select name="cylinder_size" id="cylinder_size" required
                                                class="w-full pl-4 pr-10 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white/80 backdrop-blur-sm appearance-none font-medium">
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
                                    <label for="cylinder_type_search" class="block text-sm font-semibold text-gray-700">Cylinder Type *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                        <input type="text" id="cylinder_type_search" 
                                               class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white/80 backdrop-blur-sm transition-all font-medium"
                                               placeholder="Search or select cylinder type..."
                                               autocomplete="off">
                                        <input type="hidden" name="cylinder_type" id="cylinder_type" value="{{ old('cylinder_type') }}" required>
                                    </div>
                                    
                                    <!-- Cylinder Type Dropdown -->
                                    <div id="cylinder_type_dropdown" class="hidden absolute z-40 w-full mt-1 bg-white rounded-xl shadow-lg border border-gray-200 max-h-48 overflow-y-auto">
                                        <div class="p-2 space-y-1">
                                            @php
                                                $cylinderTypes = ['Total Gas', 'K-Gas', 'Pro Gas', 'Afrigas', 'Hashi Gas', 'Sea Gas', 'Supa Gas', 'Ola Gas', 'Dalbit Gas', 'Mwanga Gas', 'Power Gas', 'Hass Gas', 'Top Gas', 'Mpishi Gas', 'E-Gas', 'Taifa Gas'];
                                            @endphp
                                            @foreach($cylinderTypes as $type)
                                                <div class="cylinder-type-option px-3 py-2 hover:bg-purple-50 cursor-pointer rounded-lg transition-colors" data-value="{{ $type }}">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                            </svg>
                                                        </div>
                                                        <span class="font-medium text-gray-900">{{ $type }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
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

                        <!-- Enhanced Payment Details -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Payment Details *</h3>
                                    <p class="text-sm text-gray-600 mt-1">Enter refill amount and payment information</p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="amount" class="block text-sm font-semibold text-gray-700">Gas Refill Amount *</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-medium">KSh</span>
                                            </div>
                                            <input type="number" name="amount" id="amount" step="0.01" min="0" 
                                                   value="{{ old('amount') }}" required
                                                   class="w-full pl-14 pr-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white/80 backdrop-blur-sm transition-all font-bold text-lg"
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
                                        <label for="deposit_amount" class="block text-sm font-semibold text-gray-700">Deposit Amount</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-medium">KSh</span>
                                            </div>
                                            <input type="number" name="deposit_amount" id="deposit_amount" step="0.01" min="0" 
                                                   value="{{ old('deposit_amount', 0) }}"
                                                   class="w-full pl-14 pr-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white/80 backdrop-blur-sm transition-all font-bold text-lg"
                                                   placeholder="0.00">
                                        </div>
                                        <p class="text-xs text-green-600 font-medium">Extra amount collected for advance collection</p>
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

                                <!-- Enhanced Payment Status -->
                                <div class="space-y-4">
                                    <label class="block text-sm font-semibold text-gray-700">Payment Status *</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <label class="payment-status-card group cursor-pointer">
                                            <input type="radio" name="payment_status" value="paid" 
                                                   class="sr-only peer"
                                                   {{ old('payment_status') === 'paid' ? 'checked' : '' }}>
                                            <div class="p-5 border-2 border-gray-200 rounded-2xl peer-checked:border-green-500 peer-checked:bg-gradient-to-br peer-checked:from-green-50 peer-checked:to-emerald-50 transition-all duration-300 hover:border-green-300 hover:shadow-lg group-hover:scale-105">
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 text-lg">Paid</h4>
                                                        <p class="text-sm text-gray-600">Customer has paid for the refill</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="payment-status-card group cursor-pointer">
                                            <input type="radio" name="payment_status" value="pending" 
                                                   class="sr-only peer"
                                                   {{ old('payment_status', 'pending') === 'pending' ? 'checked' : '' }}>
                                            <div class="p-5 border-2 border-gray-200 rounded-2xl peer-checked:border-yellow-500 peer-checked:bg-gradient-to-br peer-checked:from-yellow-50 peer-checked:to-orange-50 transition-all duration-300 hover:border-yellow-300 hover:shadow-lg group-hover:scale-105">
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg group-hover:scale-110 transition-transform">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-bold text-gray-900 text-lg">Pending</h4>
                                                        <p class="text-sm text-gray-600">Customer will pay later</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    @error('payment_status')
                                        <div class="flex items-center gap-2 text-red-600 text-sm bg-red-50 p-3 rounded-lg border border-red-200">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Notes Section -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-gray-100 to-slate-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Additional Notes</h3>
                                    <p class="text-sm text-gray-600 mt-1">Add any additional information about this transaction</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label for="notes" class="block text-sm font-semibold text-gray-700">Notes <span class="text-gray-400 font-normal">(Optional)</span></label>
                                <textarea name="notes" id="notes" rows="4" 
                                          class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500 bg-white/80 backdrop-blur-sm resize-none transition-all"
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

                    <!-- Enhanced Form Actions -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 px-8 py-6 border-t border-gray-200/50 flex flex-col sm:flex-row sm:justify-between items-center gap-6">
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            All fields marked with * are required
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                            <a href="{{ route($indexRoute) }}" 
                               class="inline-flex items-center justify-center px-8 py-3.5 border-2 border-gray-300 rounded-xl text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200 group">
                                <svg class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" id="submit-btn"
                                    class="inline-flex items-center justify-center px-10 py-3.5 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 hover:from-orange-700 hover:via-red-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200 transform hover:scale-105 group">
                                <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span id="submit-text">Create Transaction</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Enhanced Summary Card -->
            <div id="transaction_summary" class="mt-8 bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100/50 p-6" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Transaction Summary
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl border border-blue-200">
                        <div class="font-bold text-blue-800">Transaction Type</div>
                        <div id="summary_type" class="text-blue-600 mt-1 font-semibold">-</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl border border-purple-200">
                        <div class="font-bold text-purple-800">Cylinder</div>
                        <div id="summary_cylinder" class="text-purple-600 mt-1 font-semibold">-</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border border-green-200">
                        <div class="font-bold text-green-800">Payment Status</div>
                        <div id="summary_payment" class="text-green-600 mt-1 font-semibold">-</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-orange-50 to-red-50 rounded-xl border border-orange-200">
                        <div class="font-bold text-orange-800">Total Amount</div>
                        <div id="summary_amount" class="text-orange-600 mt-1 font-bold text-lg">KSh 0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Styles and JavaScript -->
    <style>
        /* Custom animations and transitions */
        .transaction-type-card:hover .w-14,
        .payment-status-card:hover .w-12 {
            transform: scale(1.1);
            transition: transform 0.3s ease-in-out;
        }
        
        /* Focus styles for better accessibility */
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        /* Loading state for submit button */
        .form-submitting {
            opacity: 0.8;
            pointer-events: none;
        }
        
        .form-submitting #submit-btn {
            background: linear-gradient(45deg, #9ca3af, #6b7280);
            transform: scale(1);
        }
        
        /* Custom dropdown animations */
        .dropdown-enter {
            animation: dropdownFadeIn 0.2s ease-out;
        }
        
        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Hover effects for search results */
        .search-result-item:hover {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            transform: translateX(4px);
            transition: all 0.2s ease;
        }
        
        .cylinder-type-option:hover {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            transform: translateX(4px);
            transition: all 0.2s ease;
        }

        /* Enhanced Add New Customer Button Styles */
        #add_new_customer_btn {
            transform: translateY(0);
            transition: all 0.2s ease-in-out;
        }

        #add_new_customer_btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }

        #add_new_customer_btn:active {
            transform: translateY(0) scale(0.95);
        }

        /* Responsive adjustments for the customer info header */
        @media (max-width: 640px) {
            #add_new_customer_btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Enhanced new customer form animation */
        #new_customer_form.dropdown-enter {
            animation: slideInDown 0.3s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Improved focus states for better accessibility */
        #add_new_customer_btn:focus {
            outline: none;
            ring: 2px;
            ring-color: rgba(34, 197, 94, 0.5);
            ring-offset: 2px;
        }

        /* Subtle glow effect on hover */
        #add_new_customer_btn:hover {
            box-shadow: 
                0 8px 25px rgba(34, 197, 94, 0.3),
                0 0 0 1px rgba(34, 197, 94, 0.2);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get route name for API calls
            const currentRoute = '{{ $currentRoute }}';
            const isPosContext = currentRoute.startsWith('pos.');
            const quickCreateUrl = isPosContext ? '{{ route("pos.customers.quick-store") }}' : '{{ route("admin.customers.quick-store") }}';
            
            // Elements
            const form = document.getElementById('cylinder-form');
            const submitBtn = document.getElementById('submit-btn');
            const submitText = document.getElementById('submit-text');
            
            // Customer dropdown elements
            const customerSearch = document.getElementById('customer_search');
            const customerDropdown = document.getElementById('customer_dropdown');
            const customerOptions = document.querySelectorAll('.customer-option');
            const newCustomerForm = document.getElementById('new_customer_form');
            const cancelNewCustomerBtn = document.getElementById('cancel_new_customer');
            const addNewCustomerBtn = document.getElementById('add_new_customer_btn');
            
            // Cylinder type elements
            const cylinderTypeSearch = document.getElementById('cylinder_type_search');
            const cylinderTypeDropdown = document.getElementById('cylinder_type_dropdown');
            const cylinderTypeOptions = document.querySelectorAll('.cylinder-type-option');
            
            // Transaction type elements
            const transactionTypeRadios = document.querySelectorAll('input[name="transaction_type"]');
            const depositSection = document.getElementById('deposit_section');
            
            // Summary elements
            const summaryCard = document.getElementById('transaction_summary');
            const summaryType = document.getElementById('summary_type');
            const summaryCylinder = document.getElementById('summary_cylinder');
            const summaryPayment = document.getElementById('summary_payment');
            const summaryAmount = document.getElementById('summary_amount');
            
            let selectedCustomerId = null;
            let customerOptionsCache = [];
            
            // Initialize customer options cache
            function initializeCustomerCache() {
                customerOptionsCache = Array.from(customerOptions).map(option => ({
                    id: option.dataset.customerId,
                    name: option.dataset.customerName,
                    phone: option.dataset.customerPhone,
                    balance: option.dataset.customerBalance,
                    searchText: option.dataset.searchText,
                    element: option
                })).filter(customer => customer.id !== 'new');
            }
            
            // Add New Customer Button functionality
            addNewCustomerBtn.addEventListener('click', function() {
                // Show new customer form with animation
                customerSearch.value = 'New Customer';
                newCustomerForm.classList.remove('hidden');
                newCustomerForm.classList.add('dropdown-enter');
                customerDropdown.classList.add('hidden');
                
                // Clear any existing customer selection
                clearCustomerSelection();
                
                // Clear form fields
                document.getElementById('customer_name').value = '';
                document.getElementById('customer_phone').value = '';
                
                // Focus on customer name field with slight delay for animation
                setTimeout(() => {
                    document.getElementById('customer_name').focus();
                }, 100);
                
                // Add visual feedback - button pressed state
                this.classList.add('scale-95');
                setTimeout(() => {
                    this.classList.remove('scale-95');
                }, 150);
            });
            
            // Customer dropdown functionality
            customerSearch.addEventListener('focus', function() {
                customerDropdown.classList.remove('hidden');
                customerDropdown.classList.add('dropdown-enter');
                filterCustomers('');
            });
            
            customerSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                filterCustomers(query);
                updateSummary();
            });
            
            function filterCustomers(query) {
                customerOptions.forEach(option => {
                    const searchText = option.dataset.searchText || '';
                    if (searchText.includes(query)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            }
            
            // Customer selection
            customerOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const customerId = this.dataset.customerId;
                    
                    if (customerId === 'new') {
                        // Show new customer form
                        customerSearch.value = 'New Customer';
                        newCustomerForm.classList.remove('hidden');
                        newCustomerForm.classList.add('dropdown-enter');
                        customerDropdown.classList.add('hidden');
                        
                        // Clear hidden fields
                        document.getElementById('customer_id').value = '';
                        document.getElementById('customer_name_hidden').value = '';
                        document.getElementById('customer_phone_hidden').value = '';
                        
                        // Focus on customer name field
                        setTimeout(() => {
                            document.getElementById('customer_name').focus();
                        }, 100);
                    } else {
                        // Select existing customer
                        const customerName = this.dataset.customerName;
                        const customerPhone = this.dataset.customerPhone;
                        const customerBalance = this.dataset.customerBalance;
                        
                        selectExistingCustomer({
                            id: customerId,
                            name: customerName,
                            phone: customerPhone,
                            balance: parseFloat(customerBalance)
                        });
                    }
                });
            });
            
            function selectExistingCustomer(customer) {
                selectedCustomerId = customer.id;
                
                // Update search field
                customerSearch.value = `${customer.name} - ${customer.phone}`;
                
                // Update hidden form fields
                document.getElementById('customer_id').value = customer.id;
                document.getElementById('customer_name_hidden').value = customer.name;
                document.getElementById('customer_phone_hidden').value = customer.phone;
                
                // Update visible form fields (for new customer form)
                document.getElementById('customer_name').value = customer.name;
                document.getElementById('customer_phone').value = customer.phone;
                
                // Hide dropdowns and forms
                customerDropdown.classList.add('hidden');
                newCustomerForm.classList.add('hidden');
                
                updateSummary();
            }
            
            // Add new customer to dropdown
            function addCustomerToDropdown(customer) {
                const customerOptionsContainer = document.getElementById('customer_options');
                const newCustomerOption = document.querySelector('[data-customer-id="new"]');
                
                // Create new customer option element
                const newOption = document.createElement('div');
                newOption.className = 'customer-option px-3 py-3 hover:bg-blue-50 cursor-pointer rounded-lg transition-colors border-b border-gray-100 last:border-b-0';
                newOption.setAttribute('data-customer-id', customer.id);
                newOption.setAttribute('data-customer-name', customer.name);
                newOption.setAttribute('data-customer-phone', customer.phone);
                newOption.setAttribute('data-customer-balance', customer.balance);
                newOption.setAttribute('data-search-text', (customer.name + ' ' + customer.phone).toLowerCase());
                
                newOption.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">${customer.name}</p>
                            <p class="text-sm text-gray-600">${customer.phone}</p>
                            <p class="text-xs text-green-600 font-medium">No outstanding balance</p>
                        </div>
                    </div>
                `;
                
                // Add click event listener
                newOption.addEventListener('click', function() {
                    selectExistingCustomer(customer);
                });
                
                // Insert before the "Add New Customer" option
                customerOptionsContainer.insertBefore(newOption, newCustomerOption);
                
                // Update cache
                customerOptionsCache.push({
                    id: customer.id,
                    name: customer.name,
                    phone: customer.phone,
                    balance: customer.balance,
                    searchText: (customer.name + ' ' + customer.phone).toLowerCase(),
                    element: newOption
                });
            }
            
            // Quick customer creation with AJAX
            function createCustomerAjax() {
                const customerName = document.getElementById('customer_name').value.trim();
                const customerPhone = document.getElementById('customer_phone').value.trim();
                
                if (!customerName || !customerPhone) {
                    showErrorMessage('Please fill in both customer name and phone number.');
                    return;
                }
                
                showLoadingState();
                
                fetch(quickCreateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: customerName,
                        phone: customerPhone
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoadingState();
                    
                    if (data.success) {
                        // Add customer to dropdown
                        addCustomerToDropdown(data.customer);
                        
                        // Select the new customer
                        selectExistingCustomer(data.customer);
                        
                        // Show success message
                        showSuccessMessage(data.message || 'Customer created successfully!');
                        
                        // Clear form
                        document.getElementById('customer_name').value = '';
                        document.getElementById('customer_phone').value = '';
                        
                    } else {
                        showErrorMessage(data.message || 'Failed to create customer');
                    }
                })
                .catch(error => {
                    hideLoadingState();
                    console.error('Error creating customer:', error);
                    showErrorMessage('Network error. Please try again.');
                });
            }
            
            // Add quick create button to new customer form
            function addQuickCreateButton() {
                const newCustomerForm = document.getElementById('new_customer_form');
                const buttonContainer = newCustomerForm.querySelector('.grid');
                
                if (!buttonContainer.querySelector('.quick-create-btn')) {
                    const quickCreateButton = document.createElement('div');
                    quickCreateButton.className = 'col-span-full mt-4';
                    quickCreateButton.innerHTML = `
                        <button type="button" id="quick-create-btn" 
                                class="quick-create-btn w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-green-700 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center space-x-2 shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span>Create Customer Now</span>
                        </button>
                    `;
                    
                    buttonContainer.appendChild(quickCreateButton);
                    
                    // Add click event listener
                    document.getElementById('quick-create-btn').addEventListener('click', createCustomerAjax);
                }
            }
            
            function showLoadingState() {
                const btn = document.getElementById('quick-create-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Creating...</span>
                    `;
                }
            }
            
            function hideLoadingState() {
                const btn = document.getElementById('quick-create-btn');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Create Customer Now</span>
                    `;
                }
            }
            
            function showSuccessMessage(message) {
                // Create a temporary success toast
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
                toast.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);
                
                // Animate out and remove
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 300);
                }, 3000);
            }
            
            function showErrorMessage(message) {
                // Create a temporary error toast
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
                toast.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>${message}</span>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);
                
                // Animate out and remove
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(toast);
                    }, 300);
                }, 5000);
            }
            
            // Enhanced cancel functionality to also clear the search field
            cancelNewCustomerBtn.addEventListener('click', function() {
                newCustomerForm.classList.add('hidden');
                customerSearch.value = '';
                document.getElementById('customer_name').value = '';
                document.getElementById('customer_phone').value = '';
                clearCustomerSelection();
                
                // Return focus to customer search
                customerSearch.focus();
            });
            
            function clearCustomerSelection() {
                selectedCustomerId = null;
                document.getElementById('customer_id').value = '';
                document.getElementById('customer_name_hidden').value = '';
                document.getElementById('customer_phone_hidden').value = '';
                updateSummary();
            }
            
            // Handle manual customer entry for new customers
            document.getElementById('customer_name').addEventListener('input', function() {
                if (newCustomerForm.classList.contains('hidden')) return;
                document.getElementById('customer_name_hidden').value = this.value;
                updateSummary();
            });
            
            document.getElementById('customer_phone').addEventListener('input', function() {
                if (newCustomerForm.classList.contains('hidden')) return;
                document.getElementById('customer_phone_hidden').value = this.value;
                updateSummary();
            });
            
            // Hide dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!customerSearch.contains(e.target) && !customerDropdown.contains(e.target)) {
                    customerDropdown.classList.add('hidden');
                }
                if (!cylinderTypeSearch.contains(e.target) && !cylinderTypeDropdown.contains(e.target)) {
                    cylinderTypeDropdown.classList.add('hidden');
                }
            });
            
            // Cylinder type autocomplete
            cylinderTypeSearch.addEventListener('focus', function() {
                cylinderTypeDropdown.classList.remove('hidden');
                cylinderTypeDropdown.classList.add('dropdown-enter');
                filterCylinderTypes('');
            });
            
            cylinderTypeSearch.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                filterCylinderTypes(query);
                updateSummary();
            });
            
            function filterCylinderTypes(query) {
                cylinderTypeOptions.forEach(option => {
                    const text = option.textContent.toLowerCase();
                    if (text.includes(query)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            }
            
            // Cylinder type selection
            cylinderTypeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.dataset.value;
                    cylinderTypeSearch.value = value;
                    document.getElementById('cylinder_type').value = value;
                    cylinderTypeDropdown.classList.add('hidden');
                    updateSummary();
                });
            });
            
            // Transaction type change handler
            transactionTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'advance_collection') {
                        depositSection.style.display = 'block';
                        depositSection.classList.add('dropdown-enter');
                    } else {
                        depositSection.style.display = 'none';
                        document.getElementById('deposit_amount').value = '0';
                    }
                    updateSummary();
                });
            });
            
            // Real-time summary updates
            ['cylinder_size', 'amount', 'deposit_amount'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', updateSummary);
                    element.addEventListener('input', updateSummary);
                }
            });
            
            document.querySelectorAll('input[name="payment_status"]').forEach(radio => {
                radio.addEventListener('change', updateSummary);
            });
            
            // Update transaction summary
            function updateSummary() {
                const transactionType = document.querySelector('input[name="transaction_type"]:checked')?.value;
                const cylinderSize = document.getElementById('cylinder_size').value;
                const cylinderType = document.getElementById('cylinder_type').value;
                const amount = parseFloat(document.getElementById('amount').value) || 0;
                const depositAmount = parseFloat(document.getElementById('deposit_amount').value) || 0;
                const paymentStatus = document.querySelector('input[name="payment_status"]:checked')?.value;
                
                if (transactionType || cylinderSize || cylinderType || amount > 0) {
                    summaryCard.style.display = 'block';
                    summaryCard.classList.add('dropdown-enter');
                    
                    // Update summary content
                    summaryType.textContent = transactionType ? 
                        (transactionType === 'drop_off' ? 'Drop-off First' : 'Advance Collection') : '-';
                    
                    summaryCylinder.textContent = (cylinderSize && cylinderType) ? 
                        `${cylinderSize} ${cylinderType}` : '-';
                    
                    summaryPayment.textContent = paymentStatus ? 
                        (paymentStatus === 'paid' ? 'Paid' : 'Pending') : '-';
                    
                    const totalAmount = amount + depositAmount;
                    summaryAmount.textContent = totalAmount > 0 ? `KSh ${totalAmount.toLocaleString()}` : 'KSh 0';
                }
            }
            
            // Enhanced form submission
            form.addEventListener('submit', function(e) {
                // Validate required fields
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                // Special validation for customer selection
                const customerId = document.getElementById('customer_id').value;
                const customerName = document.getElementById('customer_name').value;
                const customerPhone = document.getElementById('customer_phone').value;
                
                if (!customerId && (!customerName || !customerPhone)) {
                    customerSearch.classList.add('border-red-300', 'bg-red-50');
                    isValid = false;
                } else {
                    customerSearch.classList.remove('border-red-300', 'bg-red-50');
                }
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-300', 'bg-red-50');
                        field.classList.remove('border-gray-200');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-300', 'bg-red-50');
                        field.classList.add('border-gray-200');
                    }
                });
                
                if (!isValid) {
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
                submitText.innerHTML = `
                    <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating Transaction...
                `;
                submitBtn.disabled = true;
            });
            
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
                        this.classList.add('border-gray-200');
                    }
                });
            });
            
            // Initialize
            initializeCustomerCache();
            addQuickCreateButton();
            updateSummary();
        });
    </script>
</x-app-layout>