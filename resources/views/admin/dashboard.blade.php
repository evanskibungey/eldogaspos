<!-- resources/views/admin/dashboard.blade.php -->
<x-app-layout>
    <!-- CSRF Token for API calls -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <div class="py-6 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Top Action Bar with Company Name -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">{{ $companyName }} Dashboard</h2>
                    <p class="text-sm text-gray-500 mt-1">Welcome back! Here's your business at a glance.</p>
                </div>
                <div class="flex space-x-3" x-data="syncStatusAdmin()" x-init="init()">
                    <!-- Sync Status Button -->
                    <button @click="toggleSyncStatus" 
                            class="relative inline-flex items-center px-4 py-2 font-medium rounded-lg text-sm transition-all duration-200"
                            :class="pendingSyncCount > 0 ? 'bg-orange-600 hover:bg-orange-700 text-white' : 'bg-gray-600 hover:bg-gray-700 text-white'"
                            title="View sync status and pending operations">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Sync Status</span>
                        <span x-show="pendingSyncCount > 0" 
                              class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full bg-red-500 text-white"
                              x-text="pendingSyncCount">
                        </span>
                    </button>

                    <a href="{{ route('admin.cylinders.index') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Cylinder Management
                    </a>
                    <a href="{{ route('admin.reports.sales') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        View Reports
                    </a>
                    <button @click="$dispatch('open-modal', 'create-user')" class="inline-flex items-center px-4 py-2 bg-[#0077B5] hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add New User
                    </button>

                    <!-- Sync Status Panel -->
                    <div x-show="showSyncStatus" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         @click.away="showSyncStatus = false"
                         class="absolute top-12 left-0 z-50 bg-white rounded-lg shadow-xl border border-gray-200 w-80">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Sync Status
                                </h3>
                                <button @click="showSyncStatus = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-600">Connection Status</span>
                                    <span class="text-sm font-semibold flex items-center"
                                          :class="isOnline ? 'text-green-600' : 'text-red-600'">
                                        <div class="w-2 h-2 rounded-full mr-1.5"
                                             :class="isOnline ? 'bg-green-500' : 'bg-red-500'">
                                        </div>
                                        <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-600">Pending Sync</span>
                                    <span class="text-sm font-semibold"
                                          :class="pendingSyncCount > 0 ? 'text-orange-600' : 'text-gray-700'"
                                          x-text="pendingSyncCount + ' items'"></span>
                                </div>
                                
                                <template x-if="offlineSalesSummary">
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                                        <span class="text-sm font-medium text-gray-600">Offline Sales</span>
                                        <span class="text-sm font-semibold text-gray-700" 
                                              x-text="offlineSalesSummary.total_sales + ' total'"></span>
                                    </div>
                                </template>
                                
                                <div class="pt-3 border-t border-gray-200">
                                    <button @click="forceSyncNow" 
                                            :disabled="!isOnline || pendingSyncCount === 0"
                                            class="w-full px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 flex items-center justify-center"
                                            :class="isOnline && pendingSyncCount > 0 ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <span x-text="isOnline && pendingSyncCount > 0 ? 'Sync Now' : (isOnline ? 'Nothing to Sync' : 'Cannot Sync (Offline)')"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Overview -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Sales Overview
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Today's Sales -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Today's Sales</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $sales_stats['today']['count'] }}</p>
                                <p class="text-sm text-blue-600 font-medium">{{ $currencySymbol }} {{ number_format($sales_stats['today']['amount'], 2) }}</p>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- This Week's Sales -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">This Week's Sales</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $sales_stats['week']['count'] }}</p>
                                <p class="text-sm text-green-600 font-medium">{{ $currencySymbol }} {{ number_format($sales_stats['week']['amount'], 2) }}</p>
                            </div>
                            <div class="p-3 bg-green-50 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- This Month's Sales -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-[#FF6900]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">This Month's Sales</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $sales_stats['month']['count'] }}</p>
                                <p class="text-sm text-[#FF6900] font-medium">{{ $currencySymbol }} {{ number_format($sales_stats['month']['amount'], 2) }}</p>
                            </div>
                            <div class="p-3 bg-orange-50 rounded-full">
                                <svg class="w-6 h-6 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Average Sale Value -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Average Sale</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $currencySymbol }} {{ number_format($sales_stats['average_sale'], 2) }}</p>
                                <p class="text-sm text-yellow-600 font-medium">This month</p>
                            </div>
                            <div class="p-3 bg-yellow-50 rounded-full">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cylinder Management Overview -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Cylinder Management
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Active Drop-offs -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Active Drop-offs</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $cylinderStats['active_drop_offs'] }}</p>
                                <p class="text-sm text-blue-600 font-medium">Awaiting collection</p>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Advance Collections -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Advance Collections</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $cylinderStats['active_advance_collections'] }}</p>
                                <p class="text-sm text-purple-600 font-medium">Awaiting return</p>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-full">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m0 0l-7 7-7-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payments -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Payments</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $cylinderStats['pending_payments'] }}</p>
                                <p class="text-sm text-orange-600 font-medium">{{ $currencySymbol }} {{ number_format($cylinderStats['total_pending_amount'], 0) }}</p>
                            </div>
                            <div class="p-3 bg-orange-50 rounded-full">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Today Completed -->
                    <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Today Completed</p>
                                <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $cylinderStats['today_completed'] }}</p>
                                <p class="text-sm text-green-600 font-medium">Transactions finished</p>
                            </div>
                            <div class="p-3 bg-green-50 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-600">
                        @if($cylinderStats['total_pending_deposits'] > 0)
                            Total deposits held: <span class="font-semibold text-green-600">{{ $currencySymbol }} {{ number_format($cylinderStats['total_pending_deposits'], 0) }}</span>
                        @endif
                    </p>
                    <a href="{{ route('admin.cylinders.index') }}" class="text-sm text-purple-600 hover:text-purple-800 flex items-center">
                        View all cylinder transactions
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Users Stats -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Users</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $stats['total_users'] }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-full">
                            <svg class="w-6 h-6 text-[#0077B5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-600">
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                            {{ $stats['active_users'] }} Active
                        </span>
                        <span class="mx-2">•</span>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                            {{ $stats['total_cashiers'] }} Cashiers
                        </span>
                    </div>
                </div>

                <!-- Products Stats -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Products</p>
                            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $stats['total_products'] }}</p>
                            <p class="text-sm text-gray-500 mt-1">Valued at {{ $currencySymbol }} {{ number_format($totalStockValue, 2) }}</p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-full">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-600">
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">
                            {{ $stats['total_categories'] }} Categories
                        </span>
                        <span class="mx-2">•</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                            {{ $stats['active_products'] }} Active
                        </span>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow duration-200 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Low Stock Items</p>
                            <p class="text-2xl font-semibold text-red-600 mt-1">{{ $stats['low_stock_products'] }}</p>
                            <p class="text-sm text-red-600 mt-1">Global threshold: {{ $lowStockThreshold }} items</p>
                        </div>
                        <div class="p-3 bg-red-50 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('admin.products.index', ['filter' => 'low_stock']) }}" class="text-sm text-red-600 hover:text-red-800 flex items-center">
                            View low stock items
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Sales Trend Chart -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                Sales Trend
                            </h3>
                            <a href="{{ route('admin.reports.sales') }}" class="text-sm text-[#0077B5] hover:text-blue-800 flex items-center">
                                View Details
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <div class="h-64">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Chart -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2" />
                                </svg>
                                Payment Methods
                            </h3>
                        </div>
                        <div class="h-64">
                            <canvas id="paymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products and Categories -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top Products -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                Top Selling Products
                            </h3>
                            <a href="{{ route('admin.reports.sales') }}" class="text-sm text-[#0077B5] hover:text-blue-800 flex items-center">
                                View All
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($topProducts as $product)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $product->total_quantity }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ $currencySymbol }} {{ number_format($product->total_revenue, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Categories -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Top Categories
                            </h3>
                            <a href="{{ route('admin.reports.sales') }}" class="text-sm text-[#0077B5] hover:text-blue-800 flex items-center">
                                View Details
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <div class="h-64">
                            <canvas id="topCategoriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales and Low Stock Products -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Sales -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-[#FF6900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-6-8h6M5 5h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                </svg>
                                Recent Sales
                            </h3>
                            <a href="{{ route('pos.sales.history') }}" class="text-sm text-[#0077B5] hover:text-blue-800 flex items-center">
                                View All
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentSales as $sale)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('pos.sales.show', $sale) }}" class="text-[#0077B5] hover:text-blue-900">
                                                {{ $sale->receipt_number }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $sale->customer ? $sale->customer->name : 'Walk-in Customer' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $sale->created_at->format('M d, H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            {{ $currencySymbol }} {{ number_format($sale->total_amount, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Products -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Low Stock Products
                            </h3>
                            <a href="{{ route('admin.products.index') }}" class="text-sm text-[#0077B5] hover:text-blue-800 flex items-center">
                                View All
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($low_stock_products as $product)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $product->category->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-red-600 font-medium">{{ $product->stock }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $product->min_stock }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.products.edit', $product) }}" class="text-[#0077B5] hover:text-blue-900 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Update Stock
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <x-modal name="create-user" focusable>
        <form method="POST" action="{{ route('admin.users.store') }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900">Create New User</h2>
            <p class="mt-1 text-sm text-gray-600">Add a new user to the system.</p>

            <div class="mt-6 space-y-6">
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="cashier">Cashier</option>
                        <option value="admin">Admin</option>
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>
                <x-primary-button class="bg-[#FF6900] hover:bg-orange-700">
                    Create User
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Include Chart Scripts -->
    @include('admin.partials.charts-scripts')
</x-app-layout>