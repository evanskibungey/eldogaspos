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
        .menu-separator {
            border-top: 1px solid #374151;
            margin: 0.75rem 0;
        }
        .menu-section-title {
            font-size: 0.75rem;
            color: #9CA3AF;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 1rem 1rem 0.5rem 1rem;
        }
        .pos-highlight {
            background: linear-gradient(135deg, #f97316, #ea580c);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        .pos-highlight:hover {
            background: linear-gradient(135deg, #ea580c, #dc2626);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.4);
        }
    </style>

    <!-- Sidebar Overlay for Mobile -->
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
                        <span class="text-orange-500">{{ config('settings.company_name', 'Eldo') }}</span>
                    </span>
                    <span class="text-xs text-gray-400">
                        <span class="text-orange-400">POS System</span>
                    </span>
                </div>
            </div>
            <!-- Close Button for Mobile -->
            <button @click="sidebarOpen = false" 
                class="lg:hidden text-gray-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50 rounded-md p-1">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="overflow-y-auto px-3 py-4 h-[calc(100%-11rem)]">
            @if (auth()->user()->isAdmin())
                <!-- Admin Navigation -->
                <nav class="space-y-1">
                    <!-- CORE SECTION -->
                    <div class="menu-section-title">Core</div>
                    
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.dashboard') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- POS Terminal - Highlighted -->
                    <a href="{{ route('pos.dashboard') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.dashboard') ? 'pos-highlight text-white' : 'text-gray-300 hover:bg-orange-600 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>POS Terminal</span>
                        <span class="ml-auto">
                            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Live</span>
                        </span>
                    </a>

                    <!-- SALES & OPERATIONS SECTION -->
                    <div class="menu-separator"></div>
                    <div class="menu-section-title">Sales & Operations</div>

                    <!-- Sales Management -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'sales' ? null : 'sales'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.sales.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.sales.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>Sales Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'sales' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('pos.sales.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}"
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
                            x-transition:leave-end="opacity-0 transform -translate-y-2" 
                            class="mt-1 space-y-1">

                            <div class="pl-8 border-l-2 border-orange-500/30 ml-4 space-y-1">
                                <!-- Sales History -->
                                <a href="{{ route('pos.sales.history') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('pos.sales.history') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Sales History</span>
                                </a>
                                
                                <!-- Quick Sale -->
                                <a href="{{ route('pos.dashboard') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md text-gray-300 hover:text-orange-400 hover:bg-gray-800/30">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span>Quick Sale</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Credit Management -->
                    <a href="{{ route('admin.credits.index') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.credits.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.credits.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Credit Management</span>
                    </a>

                    <!-- Cylinder Management -->
                    <a href="{{ route('admin.cylinders.index') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.cylinders.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.cylinders.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Cylinder Management</span>
                    </a>

                    <!-- INVENTORY SECTION -->
                    <div class="menu-separator"></div>
                    <div class="menu-section-title">Inventory</div>

                    <!-- Inventory Management -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'inventory' ? null : 'inventory'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span>Inventory Control</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'inventory' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.categories.*') || request()->routeIs('admin.products.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}"
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
                            x-transition:leave-end="opacity-0 transform -translate-y-2" 
                            class="mt-1 space-y-1">

                            <div class="pl-8 border-l-2 border-orange-500/30 ml-4 space-y-1">
                                <!-- Products Section -->
                                <div class="text-xs text-gray-500 font-medium mb-2 mt-2">Products</div>
                                <a href="{{ route('admin.products.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.products.index') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>View All Products</span>
                                </a>
                                
                                <a href="{{ route('admin.products.create') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.products.create') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>Add New Product</span>
                                </a>

                                <!-- Categories Section -->
                                <div class="text-xs text-gray-500 font-medium mb-2 mt-3">Categories</div>
                                <a href="{{ route('admin.categories.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.categories.index') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>View All Categories</span>
                                </a>
                                
                                <a href="{{ route('admin.categories.create') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.categories.create') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span>Add New Category</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ANALYTICS SECTION -->
                    <div class="menu-separator"></div>
                    <div class="menu-section-title">Analytics</div>

                    <!-- Reports & Analytics -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'reports' ? null : 'reports'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.reports.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Reports & Analytics</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'reports' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.reports.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}"
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
                            x-transition:leave-end="opacity-0 transform -translate-y-2" 
                            class="mt-1 space-y-1">

                            <div class="pl-8 border-l-2 border-orange-500/30 ml-4 space-y-1">
                                <a href="{{ route('admin.reports.sales') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.reports.sales') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                    <span>Sales Report</span>
                                </a>
                                
                                <a href="{{ route('admin.reports.inventory') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.reports.inventory') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span>Inventory Report</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ADMINISTRATION SECTION -->
                    <div class="menu-separator"></div>
                    <div class="menu-section-title">Administration</div>

                    <!-- User Management -->
                    <div>
                        <button @click="activeDropdown = activeDropdown === 'users' ? null : 'users'"
                            class="group flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.users.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>User Management</span>
                            </div>
                            <svg :class="{ 'rotate-180': activeDropdown === 'users' }"
                                class="h-4 w-4 transform transition-transform duration-200 {{ request()->routeIs('admin.users.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}"
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
                            x-transition:leave-end="opacity-0 transform -translate-y-2" 
                            class="mt-1 space-y-1">
                            
                            <div class="pl-8 border-l-2 border-orange-500/30 ml-4 space-y-1">
                                <a href="{{ route('admin.users.index') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.users.index') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m-13.5 0a4 4 0 110-5.197m13.5 5.197a4 4 0 110-5.197" />
                                    </svg>
                                    <span>View All Users</span>
                                </a>
                                
                                <a href="{{ route('admin.users.create') }}"
                                    class="flex items-center px-3 py-2 text-sm transition-colors rounded-md {{ request()->routeIs('admin.users.create') ? 'text-orange-400 bg-gray-800/50' : 'text-gray-300 hover:text-orange-400 hover:bg-gray-800/30' }}">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span>Add New User</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <a href="{{ route('admin.settings') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('admin.settings') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('admin.settings') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>System Settings</span>
                    </a>
                </nav>

            @else
                <!-- Cashier Navigation -->
                <nav class="space-y-1">
                    <!-- MAIN SECTION -->
                    <div class="menu-section-title">Main</div>
                    
                    <!-- Dashboard -->
                    <a href="{{ route('pos.dashboard') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.dashboard') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- POS Terminal - Highlighted for Cashier -->
                    <a href="{{ route('pos.dashboard') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg pos-highlight text-white">
                        <svg class="h-5 w-5 mr-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>POS Terminal</span>
                        <span class="ml-auto">
                            <span class="bg-green-400 text-green-900 text-xs px-2 py-1 rounded-full font-medium">Active</span>
                        </span>
                    </a>

                    <!-- OPERATIONS SECTION -->
                    <div class="menu-separator"></div>
                    <div class="menu-section-title">Operations</div>

                    <!-- Sales History -->
                    <a href="{{ route('pos.sales.history') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.sales.history') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.sales.history') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Sales History</span>
                    </a>

                    <!-- Credit Management for Cashier -->
                    <a href="{{ route('pos.credits.index') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.credits.index') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.credits.index') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Credit Management</span>
                    </a>

                    <!-- Cylinder Management for Cashier -->
                    <a href="{{ route('pos.cylinders.index') }}"
                        class="group flex items-center px-4 py-2.5 text-sm font-medium transition-all duration-200 rounded-lg {{ request()->routeIs('pos.cylinders.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="h-5 w-5 mr-3 {{ request()->routeIs('pos.cylinders.*') ? 'text-orange-400' : 'text-gray-400 group-hover:text-orange-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Cylinder Management</span>
                    </a>
                </nav>
            @endif
        </div>

        <!-- User Info & Logout Section -->
        <div class="absolute bottom-0 left-0 right-0 bg-gray-900 p-4 border-t border-gray-800">
            <!-- User Info Card -->
            <div class="px-3 py-3 mb-3 rounded-lg bg-gradient-to-r from-gray-800 to-gray-700 border border-gray-600">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-orange-500 to-red-500 flex items-center justify-center text-white font-bold shadow-lg">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="ml-3 flex flex-col">
                        <span class="text-sm font-medium text-white">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-orange-400 font-medium">
                            {{ ucfirst(Auth::user()->role) }}
                            @if(Auth::user()->isAdmin())
                                <span class="ml-1 text-green-400">• Administrator</span>
                            @else
                                <span class="ml-1 text-blue-400">• Cashier</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200 text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-lg hover:shadow-xl">
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-1 flex-col main-content-transition" :class="{'lg:pl-64': sidebarOpen, 'lg:pl-0': !sidebarOpen}">
        <!-- Header - Only show when NOT on POS dashboard page -->
        @if(!request()->routeIs('pos.dashboard'))
        <div class="sticky top-0 z-10 bg-white px-4 py-3 shadow-md border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="mr-3 flex h-10 w-10 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-orange-500 transition-colors">
                        <span class="sr-only">Toggle sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <!-- Dynamic Page Title -->
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-800">
                            @if(request()->routeIs('admin.dashboard'))
                                Dashboard Overview
                            @elseif(request()->routeIs('admin.products.*'))
                                Product Management
                            @elseif(request()->routeIs('admin.categories.*'))
                                Category Management
                            @elseif(request()->routeIs('admin.users.*'))
                                User Management
                            @elseif(request()->routeIs('admin.settings'))
                                System Settings
                            @elseif(request()->routeIs('admin.reports.*'))
                                Reports & Analytics
                            @elseif(request()->routeIs('admin.credits.*'))
                                Credit Management
                            @elseif(request()->routeIs('admin.cylinders.*'))
                                Cylinder Management
                            @elseif(request()->routeIs('pos.sales.*'))
                                Sales Management
                            @elseif(request()->routeIs('pos.cylinders.*'))
                                Cylinder Management
                            @else
                                {{ config('app.name', 'EldoGas POS') }}
                            @endif
                        </h1>
                        
                        <!-- Breadcrumb or status indicator could go here -->
                        @if(request()->routeIs('pos.*') && !request()->routeIs('pos.dashboard'))
                            <span class="ml-3 text-sm text-gray-500">
                                • Point of Sale System
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Header Actions (could include notifications, quick actions, etc.) -->
                <div class="flex items-center space-x-2">
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('pos.dashboard') }}" 
                           class="inline-flex items-center px-3 py-2 border border-orange-300 shadow-sm text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Quick POS
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</div>