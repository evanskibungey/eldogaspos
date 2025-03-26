<!-- resources/views/components/sidebar-layout.blade.php -->
<div x-data="{
    sidebarOpen: true,
    activeDropdown: null,
    isMobileScreen: window.innerWidth < 768,
    init() {
        this.$watch('isMobileScreen', value => {
            if (value) {
                this.sidebarOpen = false;
            }
        });
        window.addEventListener('resize', () => {
            this.isMobileScreen = window.innerWidth < 768;
        });
        
        // Listen for event from dashboard
        window.addEventListener('sidebar-toggle', () => {
            this.sidebarOpen = !this.sidebarOpen;
        });
    }
}" class="min-h-screen bg-gray-50">
    <style>
        .main-content-transition {
            transition: padding-left 0.3s ease-in-out;
        }
    </style>

    <!-- Sidebar Overlay -->
    <div x-show="sidebarOpen && isMobileScreen" @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity lg:hidden">
    </div>

    <!-- Sidebar -->
    <div :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': !sidebarOpen
    }"
        class="fixed top-0 left-0 z-30 h-full w-64 transform bg-gray-900 transition-transform duration-300 ease-in-out shadow-xl">

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
                        <span class="text-orange-500">{{ config('settings.company_name', 'Eldo') }}</span><span class="text-orange-500"></span>
                    </span>
                    <span class="text-xs text-gray-400"><span class="text-white-500">POS</span></span>
                </div>
            </div>
            <!-- Close Button -->
            <button @click="sidebarOpen = false" class="text-gray-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50 rounded-md">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="overflow-y-auto px-3 py-4 h-[calc(100%-11rem)]">
            @if (auth()->user()->isAdmin())
                <!-- Admin Navigation -->
                <nav class="space-y-2">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Inventory Management -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'inventory' ? null : 'inventory'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>Inventory</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'inventory' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}"
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
                            <div class="pl-5 border-l border-gray-700">
                                <a href="{{ route('admin.categories.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.categories.index') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>View All Categories</span>
                                </a>
                                <a href="{{ route('admin.categories.create') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.categories.create') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Add New Category</span>
                                </a>
                            </div>

                            <!-- Products Links -->
                            <div class="pl-5 border-l border-gray-700">
                                <a href="{{ route('admin.products.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.products.index') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>View All Products</span>
                                </a>
                                <a href="{{ route('admin.products.create') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.products.create') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Add New Product</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'sales' ? null : 'sales'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.sales.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.sales.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>Sales Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'sales' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('pos.sales.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}"
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

                            <div class="pl-5 border-l border-gray-700 space-y-1">
                                <!-- POS Dashboard -->
                                <a href="{{ route('pos.dashboard') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('pos.dashboard') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>POS Terminal</span>
                                </a>
                                
                                <!-- Sales History -->
                                <a href="{{ route('pos.sales.history') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('pos.sales.history') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Sales History</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Management Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'credits' ? null : 'credits'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.credits.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.credits.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <span>Credit Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'credits' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.credits.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}"
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
                            
                            <div class="pl-5 border-l border-gray-700 space-y-1">
                                <a href="{{ route('admin.credits.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.credits.index') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Manage Credits</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'reports' ? null : 'reports'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.reports.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Reports</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'reports' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.reports.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}"
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

                            <div class="pl-5 border-l border-gray-700">
                                <a href="{{ route('admin.reports.sales') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.reports.sales') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Sales Report</span>
                                </a>
                                <a href="{{ route('admin.reports.inventory') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.reports.inventory') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Inventory Report</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Management Section -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'users' ? null : 'users'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.users.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>User Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'users' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.users.*') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}"
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
                            
                            <div class="pl-5 border-l border-gray-700 space-y-1">
                                <a href="{{ route('admin.users.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.users.index') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>View All Users</span>
                                </a>
                                <a href="{{ route('admin.users.create') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors {{ request()->routeIs('admin.users.create') ? 'text-gray-100' : 'text-gray-300 hover:text-gray-100' }}">
                                    <span>Add New User</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Settings -->
                    <a href="{{ route('admin.settings') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.settings') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.settings') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.dashboard') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                   

                    <!-- Sales History -->
                    <a href="{{ route('pos.sales.history') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.sales.history') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.sales.history') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Sales History</span>
                    </a>

                    <!-- Credit Management for Cashier -->
                    <a href="{{ route('pos.credits.index') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.credits.index') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.credits.index') ? 'text-gray-300' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Credit Management</span>
                    </a>
                    
                        
                </nav>
            @endif
        </div>

        <!-- User Info & Logout -->
        <div class="absolute bottom-0 left-0 right-0 bg-gray-900 p-4">
            <!-- User Info -->
            <div class="px-3 py-3 mb-3 rounded-lg bg-gray-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="ml-3 flex flex-col">
                        <span class="text-sm font-medium text-white">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-gray-400">{{ ucfirst(Auth::user()->role) }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 text-white bg-orange-500 hover:bg-orange-600">
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex flex-1 flex-col main-content-transition" :class="{'lg:pl-64': sidebarOpen, 'lg:pl-0': !sidebarOpen}">
        <!-- Only show header when NOT on dashboard page -->
        @if(!request()->routeIs('pos.dashboard'))
        <div class="sticky top-0 z-10 bg-white px-4 py-3 shadow-md flex items-center justify-between">
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="mr-3 flex h-10 w-10 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-orange-500">
                    <span class="sr-only">Toggle sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <!-- Page Title - Dynamic based on route -->
                <h1 class="text-xl font-semibold text-gray-800">
                    @if(request()->routeIs('admin.dashboard'))
                        Dashboard
                    @elseif(request()->routeIs('admin.products.*'))
                        Product Management
                    @elseif(request()->routeIs('admin.categories.*'))
                        Category Management
                    @elseif(request()->routeIs('admin.users.*'))
                        User Management
                    @elseif(request()->routeIs('admin.settings'))
                        System Settings
                    @elseif(request()->routeIs('admin.reports.*'))
                        Reports
                    @elseif(request()->routeIs('admin.credits.*'))
                        Credit Management
                    @elseif(request()->routeIs('pos.sales.*'))
                        Sales
                    @else
                        {{ config('app.name') }}
                    @endif
                </h1>
            </div>
        </div>
        @endif

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