<!-- resources/views/components/sidebar-layout.blade.php -->
<div x-data="{
    sidebarOpen: true,
    activeDropdown: null,
    isMobileScreen: window.innerWidth < 768,
    init() {
        this.$watch('isMobileScreen', value => {
            this.sidebarOpen = !value;
        });
        window.addEventListener('resize', () => {
            this.isMobileScreen = window.innerWidth < 768;
        });
    }
}" class="min-h-screen bg-gray-50">
    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen && isMobileScreen" @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity lg:hidden">
    </div>

    <!-- Sidebar -->
    <div :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': !sidebarOpen
    }"
        class="fixed top-0 left-0 z-30 h-full w-64 transform bg-gray-900 transition-transform duration-300 ease-in-out lg:translate-x-0 shadow-xl">

        <!-- Logo Section -->
        <div class="flex h-20 items-center justify-between px-6 border-b border-gray-800">
            <div class="flex items-center overflow-hidden">
                <div class="flex-shrink-0 bg-white rounded-full h-10 w-10 flex items-center justify-center shadow-md">
                    @if(config('settings.company_logo'))
                        <img src="{{ Storage::url(config('settings.company_logo')) }}" 
                             alt="{{ config('settings.company_name', 'EldoGas') }}" 
                             class="h-8 w-8 object-contain" />
                    @else
                        <svg class="h-8 w-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v16h10V4H7zm2 3h6v2H9V7zm0 4h6v2H9v-2zm0 4h6v2H9v-2z"/>
                        </svg>
                    @endif
                </div>
                <div class="ml-3 flex flex-col">
                    <span class="text-xl font-bold text-white">
                        <span>{{ config('settings.company_name', 'Eldo') }}</span><span class="text-orange-500">_POS</span>
                    </span>
                    <span class="text-xs text-gray-400">System</span>
                </div>
            </div>
            <!-- Mobile Close Button -->
            <button @click="sidebarOpen = false" class="text-gray-300 hover:text-white lg:hidden focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50 rounded-md">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="overflow-y-auto px-3 py-4 h-[calc(100%-5rem)]">
            @if (auth()->user()->isAdmin())
                <!-- Admin Navigation -->
                <nav class="space-y-2">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Inventory Management -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'inventory' ? null : 'inventory'"
                            class="group flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>Inventory</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'inventory' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Inventory Dropdown -->
                        <div x-show="activeDropdown === 'inventory'"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2" class="mt-1 space-y-1 pl-7">

                            <!-- Categories Links -->
                            <div class="pl-2 border-l border-gray-700">
                                <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Categories</p>
                                <a href="{{ route('admin.categories.index') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.categories.index') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.categories.index') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0h10a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>View All</span>
                                </a>
                                <a href="{{ route('admin.categories.create') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.categories.create') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.categories.create') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>Add New</span>
                                </a>
                            </div>

                            <!-- Products Links -->
                            <div class="pl-2 border-l border-gray-700">
                                <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Products</p>
                                <a href="{{ route('admin.products.index') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.products.index') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.products.index') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span>View All</span>
                                </a>
                                <a href="{{ route('admin.products.create') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.products.create') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.products.create') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>Add New</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'sales' ? null : 'sales'"
                            class="group flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos.sales.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2 {{ request()->routeIs('pos.sales.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>Sales Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'sales' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('pos.sales.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Sales Dropdown -->
                        <div x-show="activeDropdown === 'sales'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2" class="mt-1 pl-7">

                            <div class="pl-2 border-l border-gray-700 space-y-1">
                                <!-- Create New Sale -->
                                <a href="{{ route('pos.sales.create') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('pos.sales.create') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('pos.sales.create') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>New Sale</span>
                                </a>

                                <!-- Sales History -->
                                <a href="{{ route('pos.sales.history') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('pos.sales.history') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('pos.sales.history') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Sales History</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Management Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'credits' ? null : 'credits'"
                            class="group flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.credits.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2 {{ request()->routeIs('admin.credits.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span>Credit Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'credits' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.credits.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Credits Dropdown -->
                        <div x-show="activeDropdown === 'credits'"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2" class="mt-1 pl-7">
                            
                            <div class="pl-2 border-l border-gray-700">
                                <a href="{{ route('admin.credits.index') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.credits.index') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.credits.index') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                    <span>Manage Credits</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'reports' ? null : 'reports'"
                            class="group flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.reports.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2 {{ request()->routeIs('admin.reports.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Reports</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'reports' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.reports.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Reports Dropdown -->
                        <div x-show="activeDropdown === 'reports'"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2" class="mt-1 pl-7">

                            <!-- Sales Reports -->
                            <div class="pl-2 border-l border-gray-700">
                                <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Sales Reports</p>
                                <a href="{{ route('admin.reports.sales') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.reports.sales') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.reports.sales') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Sales Analysis</span>
                                </a>
                            </div>

                            <!-- Inventory Reports -->
                            <div class="pl-2 border-l border-gray-700">
                                <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Inventory Reports</p>
                                <a href="{{ route('admin.reports.inventory') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.reports.inventory') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.reports.inventory') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    <span>Inventory Status</span>
                                </a>
                                <a href="{{ route('admin.reports.stock-movements') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.reports.stock-movements') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.reports.stock-movements') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                    <span>Stock Movements</span>
                                </a>
                            </div>

                            <!-- User Reports -->
                            <div class="pl-2 border-l border-gray-700">
                                <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-gray-500">User Reports</p>
                                <a href="{{ route('admin.reports.users') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.reports.users') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.reports.users') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>User Performance</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'users' ? null : 'users'"
                            class="group flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2 {{ request()->routeIs('admin.users.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>User Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'users' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.users.*') ? 'text-orange-500' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Users Dropdown -->
                        <div x-show="activeDropdown === 'users'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform -translate-y-2" class="mt-1 pl-7">
                            
                            <div class="pl-2 border-l border-gray-700 space-y-1">
                                <a href="{{ route('admin.users.index') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.users.index') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.users.index') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <span>View All Users</span>
                                </a>
                                <a href="{{ route('admin.users.create') }}"
                                    class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.users.create') ? 'text-orange-400' : 'text-gray-300 hover:text-orange-400' }}">
                                    <svg class="h-4 w-4 mr-2 {{ request()->routeIs('admin.users.create') ? 'text-orange-500' : 'text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span>Add New User</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Settings -->
                    <a href="{{ route('admin.settings') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('admin.settings') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('admin.settings') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Settings</span>
                    </a>
                </nav>
            @else
                <!-- Cashier Navigation -->
                <nav class="space-y-2">
                    <!-- POS Dashboard -->
                    <a href="{{ route('pos.dashboard') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos.dashboard') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('pos.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>POS Dashboard</span>
                    </a>

                    <!-- New Sale -->
                    <a href="{{ route('pos.sales.create') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos.sales.create') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('pos.sales.create') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>New Sale</span>
                    </a>

                    <!-- Sales History -->
                    <a href="{{ route('pos.sales.history') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos.sales.history') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('pos.sales.history') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Sales History</span>
                    </a>

                    <!-- Credit Management for Cashier -->
                    <a href="{{ route('pos.credits.index') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos.credits.index') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('pos.credits.index') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Credit Management</span>
                    </a>
                    
                    <!-- Reports for Cashier -->
                    <a href="{{ route('pos.reports') }}"
                        class="group flex items-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('pos.reports') || request()->routeIs('pos.reports.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-2 {{ request()->routeIs('pos.reports') || request()->routeIs('pos.reports.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>My Reports</span>
                    </a>
                </nav>
            @endif

            <!-- Logout & User Info -->
            <div class="mt-auto pt-4">
                <!-- User Info -->
                <div class="mb-3 mx-3 flex items-center px-3 py-3 rounded-lg bg-gray-800">
                    <div class="flex-shrink-0">
                        <div class="h-9 w-9 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="ml-3 flex flex-col overflow-hidden">
                        <span class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-gray-400">{{ ucfirst(Auth::user()->role) }}</span>
                    </div>
                </div>
                
                <!-- Logout Button -->
                <form method="POST" action="{{ route('logout') }}" class="px-3">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 text-white bg-orange-600 hover:bg-orange-700">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-1 flex-col lg:pl-64">
        <!-- Mobile Header -->
        <div class="sticky top-0 z-10 bg-white px-2 py-2 shadow-md sm:px-4 lg:hidden">
            <div class="flex items-center justify-between">
                <button @click="sidebarOpen = true"
                    class="flex h-10 w-10 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-orange-500">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <!-- Mobile Logo -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white rounded-full h-8 w-8 flex items-center justify-center shadow">
                        @if(config('settings.company_logo'))
                            <img src="{{ Storage::url(config('settings.company_logo')) }}" 
                                alt="{{ config('settings.company_name', 'EldoGas') }}" 
                                class="h-6 w-6 object-contain" />
                        @else
                            <svg class="h-6 w-6 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm0 2v16h10V4H7zm2 3h6v2H9V7zm0 4h6v2H9v-2zm0 4h6v2H9v-2z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-2 flex flex-col">
                        <span class="text-base font-bold text-gray-900">
                            <span>{{ config('settings.company_name', 'Eldo') }}</span><span class="text-orange-500">_POS</span>
                        </span>
                    </div>
                </div>
                
                <!-- Spacer for centering -->
                <div class="w-10"></div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-1">
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</div>