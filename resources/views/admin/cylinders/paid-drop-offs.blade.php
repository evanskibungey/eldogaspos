<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
        <div class="max-w-7xl mx-auto px-2 sm:px-3 lg:px-4">
@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';
@endphp

            <!-- Compact Header Section -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-3 overflow-hidden">
                <div class="px-4 py-3">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <!-- Left: Title Section -->
                        <div class="flex items-center gap-3">
                            <a href="{{ route($indexRoute) }}"
                               class="flex items-center justify-center w-9 h-9 bg-gray-100 hover:bg-green-50 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                               aria-label="Back to cylinders">
                                <svg class="w-5 h-5 text-gray-600 group-hover:text-green-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                            </a>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center justify-center w-9 h-9 bg-green-600 rounded-lg" aria-hidden="true">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-lg font-bold text-gray-900">Paid Drop-offs</h1>
                                    <p class="text-xs text-gray-700">Payment received transactions</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Filters & Actions -->
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter and export options">
                            <!-- Period Buttons -->
                            <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs', ['period' => 'daily']) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $period === 'daily' ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-100 text-gray-800 hover:bg-green-50 hover:text-green-600 hover:shadow-sm' }}"
                               aria-label="Filter by daily"
                               aria-current="{{ $period === 'daily' ? 'true' : 'false' }}">
                                Daily
                            </a>
                            <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs', ['period' => 'weekly']) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $period === 'weekly' ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-100 text-gray-800 hover:bg-green-50 hover:text-green-600 hover:shadow-sm' }}"
                               aria-label="Filter by weekly"
                               aria-current="{{ $period === 'weekly' ? 'true' : 'false' }}">
                                Weekly
                            </a>
                            <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs', ['period' => 'monthly']) }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $period === 'monthly' ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-100 text-gray-800 hover:bg-green-50 hover:text-green-600 hover:shadow-sm' }}"
                               aria-label="Filter by monthly"
                               aria-current="{{ $period === 'monthly' ? 'true' : 'false' }}">
                                Monthly
                            </a>
                            <button type="button" onclick="toggleCustomDateRange()"
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $period === 'custom' ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-100 text-gray-800 hover:bg-green-50 hover:text-green-600 hover:shadow-sm' }}"
                               aria-label="Filter by custom date range"
                               aria-current="{{ $period === 'custom' ? 'true' : 'false' }}">
                                Custom
                            </button>

                            <!-- Divider -->
                            <div class="hidden sm:block w-px h-6 bg-gray-300" aria-hidden="true"></div>

                            <!-- Customer History Button -->
                            <button type="button" onclick="openCustomerSearch()"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-orange-500 text-white hover:bg-orange-600 hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                               aria-label="View customer history">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Customer
                            </button>

                            <!-- Export Button -->
                            <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs.export' : 'admin.cylinders.paid-drop-offs.export', ['period' => $period, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-800 text-white hover:bg-gray-900 hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-700 focus:ring-offset-2"
                               aria-label="Export data to CSV">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Search Modal -->
            <div id="customerSearchModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300" style="margin: 0; padding: 16px;">
                <div class="bg-white rounded-2xl shadow-2xl w-full overflow-hidden transform transition-all duration-300 scale-100" style="max-width: 672px; max-height: 85vh;" onclick="event.stopPropagation()">
                    <!-- Header with Gradient -->
                    <div class="relative bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    Search Customer History
                                </h3>
                                <p class="text-orange-100 text-sm mt-1 ml-14">Find and view customer transaction details</p>
                            </div>
                            <button onclick="closeCustomerSearch()"
                                    class="text-white/80 hover:text-white hover:bg-white/20 p-2 rounded-lg transition-all duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Search Input Section -->
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" id="customerSearchInput"
                                   placeholder="Type customer name or phone number..."
                                   class="w-full pl-12 pr-4 py-4 bg-white border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 text-gray-900 placeholder-gray-400 shadow-sm"
                                   oninput="searchCustomers(this.value)"
                                   autofocus>
                        </div>
                        <!-- Results Counter -->
                        <div id="resultsCounter" class="mt-3 text-sm text-gray-600 hidden">
                            <span class="font-semibold text-orange-600" id="resultCount">0</span> customers found
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="p-6 bg-white">
                        <div id="customerSearchResults" class="max-h-96 overflow-y-auto space-y-3 custom-scrollbar">
                            <!-- Empty State -->
                            <div class="flex flex-col items-center justify-center py-12">
                                <div class="bg-gray-100 rounded-full p-4 mb-4">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 font-medium">Start typing to search customers</p>
                                <p class="text-gray-400 text-sm mt-1">Search by name or phone number</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Scrollbar Style -->
            <style>
                .custom-scrollbar::-webkit-scrollbar {
                    width: 8px;
                }
                .custom-scrollbar::-webkit-scrollbar-track {
                    background: #f1f1f1;
                    border-radius: 10px;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb {
                    background: #cbd5e1;
                    border-radius: 10px;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                    background: #94a3b8;
                }
            </style>

            <script>
            let customers = [];
            const isPosContext = {{ $isPosContext ? 'true' : 'false' }};

            function openCustomerSearch() {
                document.getElementById('customerSearchModal').classList.remove('hidden');
                document.getElementById('customerSearchInput').focus();
                if (customers.length === 0) {
                    fetchCustomers();
                }
            }

            function closeCustomerSearch() {
                document.getElementById('customerSearchModal').classList.add('hidden');
                document.getElementById('customerSearchInput').value = '';
                document.getElementById('resultsCounter').classList.add('hidden');
                document.getElementById('customerSearchResults').innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12">
                        <div class="bg-gray-100 rounded-full p-4 mb-4">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">Start typing to search customers</p>
                        <p class="text-gray-400 text-sm mt-1">Search by name or phone number</p>
                    </div>
                `;
            }

            // Close modal when clicking outside
            document.getElementById('customerSearchModal')?.addEventListener('click', function(e) {
                if (e.target === this) closeCustomerSearch();
            });

            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeCustomerSearch();
            });

            async function fetchCustomers() {
                try {
                    const response = await fetch('/api/customers');
                    customers = await response.json();
                } catch (error) {
                    console.error('Error fetching customers:', error);
                    document.getElementById('customerSearchResults').innerHTML = '<p class="text-sm text-red-500 text-center py-8">Error loading customers</p>';
                }
            }

            function searchCustomers(query) {
                const results = document.getElementById('customerSearchResults');
                const counter = document.getElementById('resultsCounter');
                const countSpan = document.getElementById('resultCount');

                if (!query.trim()) {
                    counter.classList.add('hidden');
                    results.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="bg-gray-100 rounded-full p-4 mb-4">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 font-medium">Start typing to search customers</p>
                            <p class="text-gray-400 text-sm mt-1">Search by name or phone number</p>
                        </div>
                    `;
                    return;
                }

                const filtered = customers.filter(c =>
                    c.name.toLowerCase().includes(query.toLowerCase()) ||
                    c.phone.includes(query)
                );

                // Update counter
                countSpan.textContent = filtered.length;
                counter.classList.remove('hidden');

                if (filtered.length === 0) {
                    results.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="bg-red-50 rounded-full p-4 mb-4">
                                <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <p class="text-gray-700 font-medium">No customers found</p>
                            <p class="text-gray-500 text-sm mt-1">Try searching with a different name or phone number</p>
                        </div>
                    `;
                    return;
                }

                const routePrefix = isPosContext ? 'pos' : 'admin';

                function getInitials(name) {
                    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                }

                function getAvatarColor(name) {
                    const colors = [
                        'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-pink-500',
                        'bg-indigo-500', 'bg-yellow-500', 'bg-red-500', 'bg-teal-500'
                    ];
                    const index = name.charCodeAt(0) % colors.length;
                    return colors[index];
                }

                results.innerHTML = filtered.map(customer => `
                    <a href="/${routePrefix}/cylinders/customer/${customer.id}/history"
                       class="group block p-4 bg-gradient-to-r from-white to-gray-50 hover:from-orange-50 hover:to-orange-100 border-2 border-gray-200 hover:border-orange-300 rounded-xl transition-all duration-200 transform hover:scale-[1.02] hover:shadow-lg">
                        <div class="flex items-center gap-4">
                            <div class="flex-shrink-0">
                                <div class="${getAvatarColor(customer.name)} w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:scale-110 transition-transform duration-200">
                                    ${getInitials(customer.name)}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-bold text-gray-900 group-hover:text-orange-700 transition-colors text-base truncate">
                                        ${customer.name}
                                    </h4>
                                    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">
                                        Customer
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span class="font-medium">${customer.phone}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-orange-100 group-hover:bg-orange-600 flex items-center justify-center transition-colors duration-200">
                                    <svg class="w-5 h-5 text-orange-600 group-hover:text-white transition-colors duration-200 transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>
                `).join('');
            }
            </script>

            <!-- Custom Date Range Form -->
            <div id="customDateRangeForm" class="mb-3 {{ $period === 'custom' ? '' : 'hidden' }}">
                <form method="GET" action="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs') }}" class="bg-white rounded-xl shadow-md p-4">
                    <input type="hidden" name="period" value="custom">
                    <div class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label for="start_date" class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                            <input type="date"
                                   id="start_date"
                                   name="start_date"
                                   value="{{ $startDate ?? '' }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                            <input type="date"
                                   id="end_date"
                                   name="end_date"
                                   value="{{ $endDate ?? '' }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                   required>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-all shadow-md">
                                Apply
                            </button>
                            <button type="button" onclick="resetDateRange()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all">
                                Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-2">
                <!-- Paid Drop-offs Count -->
                <div class="bg-white rounded-xl shadow-md p-3 border-2 border-green-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Paid Drop-offs ({{ ucfirst($period) }})</p>
                            <p class="text-2xl font-bold text-green-600">{{ $stats['paid_drop_offs'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Cylinders Dropped -->
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-blue-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Total Dropped</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $stats['total_cylinders_dropped'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Cylinders Collected -->
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-purple-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m0 0l-7 7-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Collected</p>
                            <p class="text-2xl font-bold text-purple-600">{{ $stats['total_cylinders_collected'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Advance Collections -->
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-orange-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Advance</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $stats['total_advance_collections'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Paid Drop-off Transactions</h3>
                        </div>
                        <span class="text-sm text-gray-600 font-semibold">{{ $transactions->total() }} total</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Products</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-green-50 transition-all duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ $transaction->reference_number }}</div>
                                        @php $ageStatus = $transaction->getAgeStatus(); @endphp
                                        <div claList ss="text-xs mt-2 px-2.5 py-1 rounded-full font-semibold {{ $ageStatus['color'] }} inline-block">
                                            {{ $ageStatus['days'] }} days waiting
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', $transaction->customer) }}"
                                           class="flex items-center gap-2 group hover:bg-orange-50 p-1 rounded-lg transition-all duration-200">
                                            <div class="w-10 h-10 bg-green-100 group-hover:bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                                                <svg class="w-5 h-5 text-green-600 group-hover:text-orange-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">{{ $transaction->customer_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $transaction->customer_phone }}</div>
                                            </div>
                                            <svg class="w-4 h-4 text-gray-400 group-hover:text-orange-600 opacity-0 group-hover:opacity-100 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($transaction->items->count() > 0)
                                            <div class="text-sm space-y-2">
                                                @foreach($transaction->items as $item)
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-semibold text-gray-900">{{ $item->product->name }}</span>
                                                            @if($item->brand)
                                                                <span class="text-xs px-2.5 py-1 bg-green-100 text-green-800 rounded-full font-medium">{{ $item->brand }}</span>
                                                            @endif
                                                        </div>
                                                        <span class="text-xs px-2.5 py-1 bg-gray-200 text-gray-700 rounded-full font-bold flex-shrink-0">×{{ $item->quantity }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="text-xs text-gray-500 mt-2 font-medium">{{ $transaction->items->count() }} item(s)</div>
                                        @else
                                            <span class="text-sm text-gray-400 italic">No items</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">KSh {{ number_format($transaction->amount, 0) }}</div>
                                        <span class="inline-flex items-center px-3 py-1.5 mt-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                            Paid
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <div class="font-semibold text-gray-900">{{ $transaction->drop_off_date->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $transaction->drop_off_date->format('h:i A') }}</div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route($isPosContext ? 'pos.cylinders.show' : 'admin.cylinders.show', $transaction) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-200 rounded-lg transition-all font-semibold">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                View
                                            </a>

                                            @if($transaction->isActive())
                                                <form method="POST" action="{{ route($isPosContext ? 'pos.cylinders.complete' : 'admin.cylinders.complete', $transaction) }}"
                                                      class="inline"
                                                      data-confirm
                                                      data-confirm-title="Complete this collection?"
                                                      data-confirm-message="{{ $transaction->customer_name }} is collecting their refilled cylinders."
                                                      @if($transaction->isPending())
                                                          data-confirm-amount="This also records payment of KSh {{ number_format($transaction->getTotalAmount(), 2) }} as received."
                                                          data-confirm-action="Complete &amp; mark paid"
                                                      @else
                                                          data-confirm-action="Complete"
                                                      @endif
                                                      data-confirm-variant="success">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-orange-100 text-orange-700 hover:bg-blue-200 rounded-lg transition-all font-semibold">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Complete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-xl font-bold text-gray-900 mb-2">No paid drop-offs found</p>
                                            <p class="text-sm text-gray-600 mb-4">No paid drop-off transactions for {{ $period }} period</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $transactions->appends(['period' => $period, 'start_date' => $startDate, 'end_date' => $endDate])->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function toggleCustomDateRange() {
            const form = document.getElementById('customDateRangeForm');
            form.classList.toggle('hidden');
        }

        function resetDateRange() {
            window.location.href = '{{ route($isPosContext ? "pos.cylinders.paid-drop-offs" : "admin.cylinders.paid-drop-offs", ["period" => "daily"]) }}';
        }
    </script>
</x-app-layout>
