<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
        <div class="max-w-7xl mx-auto px-2 sm:px-3 lg:px-4">

            <!-- Page Header -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-4 overflow-hidden">
                <div class="px-6 py-4">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                Customer Management
                            </h1>
                            <p class="text-sm text-gray-600 mt-1">Manage and view all customer information</p>
                        </div>
                        <a href="{{ route('admin.customers.create') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-xl font-semibold transition-all duration-200 shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Customer
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-4 p-5">
                <form method="GET" action="{{ route('admin.customers.index') }}" id="searchForm" class="flex flex-col lg:flex-row gap-4" style="display: flex; flex-direction: column; gap: 16px;">
                    <style>
                        @media (min-width: 1024px) {
                            #searchForm {
                                flex-direction: row !important;
                                align-items: stretch;
                            }
                            #searchForm > .flex-1 {
                                flex: 1;
                            }
                            #searchForm > div[style*="max-width: 224px"] {
                                width: 224px !important;
                            }
                        }
                    </style>
                    <!-- Autocomplete Search Input -->
                    <div class="flex-1 relative" id="autocomplete-container" style="position: relative;">
                        <div class="relative" style="position: relative;">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none" style="position: absolute; left: 0; top: 0; bottom: 0; padding-left: 16px; display: flex; align-items: center;">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   name="search"
                                   id="customer-search"
                                   value="{{ request('search') }}"
                                   placeholder="Type customer name or phone to search..."
                                   autocomplete="off"
                                   class="w-full pl-12 pr-10 py-3 text-base border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 placeholder-gray-400"
                                   style="width: 100%; padding-left: 48px; padding-right: 40px; padding-top: 12px; padding-bottom: 12px; font-size: 16px; border: 2px solid #e5e7eb; border-radius: 12px;">
                            <!-- Clear button -->
                            <button type="button"
                                    id="clear-search"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors hidden">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Autocomplete Dropdown -->
                        <div id="autocomplete-dropdown"
                             class="absolute left-0 right-0 z-50 w-full mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden hidden"
                             style="top: 100%; max-width: 100%;">
                            <div id="autocomplete-results" class="overflow-y-auto" style="max-height: 320px;">
                                <!-- Results will be populated here -->
                            </div>
                            <div id="autocomplete-loading" class="hidden p-4 text-center">
                                <div class="inline-flex items-center gap-2 text-gray-500">
                                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Searching...</span>
                                </div>
                            </div>
                            <div id="autocomplete-empty" class="hidden p-4 text-center text-gray-500">
                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="font-medium">No customers found</p>
                                <p class="text-sm">Try a different search term</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full lg:w-56" style="width: 100%; max-width: 224px;">
                        <div class="relative" style="position: relative;">
                            <select name="status"
                                    id="status-filter"
                                    onchange="document.getElementById('searchForm').submit()"
                                    class="w-full appearance-none pl-4 pr-10 py-3 text-base border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all duration-200 bg-white cursor-pointer"
                                    style="width: 100%; padding-left: 16px; padding-right: 40px; padding-top: 12px; padding-bottom: 12px; font-size: 16px; border: 2px solid #e5e7eb; border-radius: 12px; background-color: white; -webkit-appearance: none; -moz-appearance: none; appearance: none;">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Only</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" style="position: absolute; right: 0; top: 0; bottom: 0; padding-right: 12px; display: flex; align-items: center; pointer-events: none;">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2" style="display: flex; gap: 8px; flex-shrink: 0;">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-xl font-semibold transition-all duration-200 shadow-md hover:shadow-lg"
                                style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(to right, #f97316, #ea580c); color: white; border-radius: 12px; font-weight: 600; border: none; cursor: pointer;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search
                        </button>

                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.customers.index') }}"
                               class="inline-flex items-center gap-2 px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-200"
                               style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background-color: #f3f4f6; color: #374151; border-radius: 12px; font-weight: 600; text-decoration: none;">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                <!-- Active Filters Display -->
                @if(request()->hasAny(['search', 'status']))
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-500">Active filters:</span>
                            @if(request('search'))
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    "{{ request('search') }}"
                                    <a href="{{ route('admin.customers.index', array_merge(request()->except('search'))) }}" class="ml-1 hover:text-orange-900">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 {{ request('status') == 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }} rounded-lg text-sm font-medium">
                                    <span class="w-2 h-2 rounded-full {{ request('status') == 'active' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ ucfirst(request('status')) }}
                                    <a href="{{ route('admin.customers.index', array_merge(request()->except('status'))) }}" class="ml-1 hover:opacity-70">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Results Summary -->
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ $customers->firstItem() ?? 0 }}</span>
                    to <span class="font-semibold text-gray-900">{{ $customers->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-gray-900">{{ $customers->total() }}</span> customers
                </p>
            </div>

            <!-- Customers Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                @if($customers->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Balance</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($customers as $customer)
                                    <tr class="hover:bg-orange-50/50 transition-all duration-200 group">
                                        <!-- Customer Name with Avatar -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm shadow-md shadow-orange-500/20 group-hover:shadow-lg group-hover:shadow-orange-500/30 transition-all duration-200">
                                                    {{ strtoupper(substr($customer->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">{{ $customer->name }}</div>
                                                    <div class="text-xs text-gray-400">ID: #{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Contact Info -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $customer->phone }}</div>
                                                    @if($customer->email)
                                                        <div class="text-xs text-gray-500">{{ $customer->email }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($customer->status == 'active')
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold">
                                                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
                                                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Balance -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($customer->balance > 0)
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 rounded-lg">
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                                    </svg>
                                                    <span class="text-sm font-bold text-emerald-600">KSh {{ number_format($customer->balance, 2) }}</span>
                                                </div>
                                            @elseif($customer->balance < 0)
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 rounded-lg">
                                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                                    </svg>
                                                    <span class="text-sm font-bold text-red-600">KSh {{ number_format($customer->balance, 2) }}</span>
                                                </div>
                                            @else
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg">
                                                    <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                                    <span class="text-sm font-medium text-gray-500">KSh 0.00</span>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.customers.show', $customer) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 rounded-lg transition-all duration-200 font-medium text-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="{{ route('admin.customers.edit', $customer) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-orange-50 text-orange-600 hover:bg-orange-100 hover:text-orange-700 rounded-lg transition-all duration-200 font-medium text-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $customers->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-16">
                        <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-gray-700 font-semibold text-lg mb-1">No customers found</p>
                        <p class="text-gray-500 text-sm mb-6">
                            @if(request()->hasAny(['search', 'status']))
                                Try adjusting your search or filter criteria
                            @else
                                Start by adding your first customer
                            @endif
                        </p>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.customers.index') }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Clear Filters
                            </a>
                        @else
                            <a href="{{ route('admin.customers.create') }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-xl font-semibold transition-all duration-200 shadow-lg shadow-orange-500/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Customer
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Autocomplete JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('customer-search');
        const dropdown = document.getElementById('autocomplete-dropdown');
        const results = document.getElementById('autocomplete-results');
        const loading = document.getElementById('autocomplete-loading');
        const empty = document.getElementById('autocomplete-empty');
        const clearBtn = document.getElementById('clear-search');
        const form = document.getElementById('searchForm');

        let debounceTimer;
        let selectedIndex = -1;
        let currentResults = [];

        // Show/hide clear button based on input
        function toggleClearButton() {
            if (searchInput.value.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
        }

        // Clear search input
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            toggleClearButton();
            hideDropdown();
            searchInput.focus();
        });

        // Search input handler
        searchInput.addEventListener('input', function() {
            toggleClearButton();
            const query = this.value.trim();

            clearTimeout(debounceTimer);

            if (query.length < 2) {
                hideDropdown();
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchResults(query);
            }, 300);
        });

        // Fetch results from API
        async function fetchResults(query) {
            showLoading();

            try {
                const response = await fetch(`{{ route('admin.customers.search') }}?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                currentResults = data;
                selectedIndex = -1;

                if (data.length > 0) {
                    showResults(data);
                } else {
                    showEmpty();
                }
            } catch (error) {
                console.error('Search error:', error);
                showEmpty();
            }
        }

        // Show loading state
        function showLoading() {
            dropdown.classList.remove('hidden');
            results.classList.add('hidden');
            loading.classList.remove('hidden');
            empty.classList.add('hidden');
        }

        // Show results
        function showResults(data) {
            results.innerHTML = data.map((customer, index) => `
                <a href="{{ route('admin.customers.show', '') }}/${customer.id}"
                   class="autocomplete-item flex items-center gap-3 px-4 py-3 hover:bg-orange-50 transition-colors cursor-pointer border-b border-gray-100 last:border-b-0"
                   data-index="${index}">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
                        ${customer.name.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-900 truncate">${highlightMatch(customer.name, searchInput.value)}</div>
                        <div class="text-xs text-gray-500">${highlightMatch(customer.phone, searchInput.value)}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-medium ${customer.balance > 0 ? 'text-emerald-600' : (customer.balance < 0 ? 'text-red-600' : 'text-gray-500')}">
                            KSh ${parseFloat(customer.balance).toLocaleString('en-KE', {minimumFractionDigits: 2})}
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            `).join('');

            // Add search all option
            results.innerHTML += `
                <button type="submit" form="searchForm"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium text-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Search all for "${searchInput.value}"
                </button>
            `;

            dropdown.classList.remove('hidden');
            results.classList.remove('hidden');
            loading.classList.add('hidden');
            empty.classList.add('hidden');
        }

        // Highlight matching text
        function highlightMatch(text, query) {
            const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
            return text.replace(regex, '<span class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</span>');
        }

        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // Show empty state
        function showEmpty() {
            dropdown.classList.remove('hidden');
            results.classList.add('hidden');
            loading.classList.add('hidden');
            empty.classList.remove('hidden');
        }

        // Hide dropdown
        function hideDropdown() {
            dropdown.classList.add('hidden');
            selectedIndex = -1;
        }

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const items = results.querySelectorAll('.autocomplete-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex].click();
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });

        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('bg-orange-50');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-orange-50');
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!document.getElementById('autocomplete-container').contains(e.target)) {
                hideDropdown();
            }
        });

        // Focus handler
        searchInput.addEventListener('focus', function() {
            if (this.value.length >= 2 && currentResults.length > 0) {
                dropdown.classList.remove('hidden');
            }
        });

        // Initialize clear button state
        toggleClearButton();
    });
    </script>
</x-app-layout>
