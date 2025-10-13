<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $createRoute = $isPosContext ? 'pos.cylinders.create' : 'admin.cylinders.create';
    $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';
@endphp

            <!-- Enhanced Header with Icon -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-16 h-16 bg-orange-500 rounded-2xl shadow-lg">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Cylinder Management</h1>
                            <p class="text-gray-600 mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Manage customer cylinder drop-offs and collections
                            </p>
                        </div>
                    </div>
                    <a href="{{ route($createRoute) }}"
                       class="inline-flex items-center justify-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Transaction
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $isPosContext ? '3' : '5' }} gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg hover:border-orange-300 transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-orange-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Active Drop-offs</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $stats['active_drop_offs'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg hover:border-orange-300 transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-orange-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m0 0l-7 7-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Advance Collections</p>
                            <p class="text-2xl font-bold text-orange-600">{{ $stats['active_advance_collections'] }}</p>
                        </div>
                    </div>
                </div>

@if($isPosContext)
                    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg hover:border-green-300 transition-all duration-200">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-green-100 rounded-lg flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Today Completed</p>
                                <p class="text-2xl font-bold text-green-600">{{ $stats['today_completed'] }}</p>
                            </div>
                        </div>
                    </div>
@else
                    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg hover:border-orange-300 transition-all duration-200">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-orange-100 rounded-lg flex-shrink-0">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Pending Payments</p>
                                <p class="text-2xl font-bold text-orange-600">{{ $stats['pending_payments'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg hover:border-red-300 transition-all duration-200">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-red-100 rounded-lg flex-shrink-0">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Pending Amount</p>
                                <p class="text-xl font-bold text-red-600 truncate">KSh {{ number_format($stats['total_pending_amount'], 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border border-gray-200 hover:shadow-lg hover:border-green-300 transition-all duration-200">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-green-100 rounded-lg flex-shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Pending Deposits</p>
                                <p class="text-xl font-bold text-green-600 truncate">KSh {{ number_format($stats['total_pending_deposits'], 0) }}</p>
                            </div>
                        </div>
                    </div>
@endif
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Filter Transactions</h3>
                </div>
                <form method="GET" action="{{ route($indexRoute) }}" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Search by customer name, phone, or reference..."
                                   class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all">
                            <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <select name="status" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-medium transition-all">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <select name="type" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-medium transition-all">
                            <option value="">All Types</option>
                            <option value="drop_off" {{ request('type') === 'drop_off' ? 'selected' : '' }}>Drop-off</option>
                            <option value="advance_collection" {{ request('type') === 'advance_collection' ? 'selected' : '' }}>Advance Collection</option>
                        </select>
                    </div>

                    <div>
                        <select name="payment_status" class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-medium transition-all">
                            <option value="">All Payments</option>
                            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    <button type="submit" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl transition-all shadow-md hover:shadow-lg transform hover:scale-105 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter
                    </button>

                    @if(request()->hasAny(['search', 'status', 'type', 'payment_status']))
                        <a href="{{ route($indexRoute) }}" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Transactions</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Products</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-orange-50 transition-all duration-150">
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ $transaction->reference_number }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $transaction->getDaysWaiting() }} days ago</div>
                                    </td>

                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">{{ $transaction->customer_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $transaction->customer_phone }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        @if($transaction->items->count() > 0)
                                            <div class="text-sm space-y-2">
                                                @foreach($transaction->items as $item)
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-semibold text-gray-900">{{ $item->product->name }}</span>
                                                            @if($item->brand)
                                                                <span class="text-xs px-2.5 py-1 bg-orange-100 text-orange-800 rounded-full font-medium">{{ $item->brand }}</span>
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
                                    
                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $transaction->getTransactionTypeBadgeColor() }}">
                                            {{ $transaction->isDropOff() ? 'Drop-off' : 'Advance Collection' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">KSh {{ number_format($transaction->amount, 0) }}</div>
                                        @if($transaction->deposit_amount > 0)
                                            <div class="text-xs text-green-600 mt-1 font-medium">+ KSh {{ number_format($transaction->deposit_amount, 0) }} deposit</div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-5 whitespace-nowrap">
                                        <div class="flex flex-col space-y-2">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $transaction->getStatusBadgeColor() }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $transaction->getPaymentStatusBadgeColor() }}">
                                                {{ ucfirst($transaction->payment_status) }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 whitespace-nowrap text-sm">
                                        <div class="font-semibold text-gray-900">{{ $transaction->drop_off_date->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $transaction->drop_off_date->format('h:i A') }}</div>
                                    </td>

                                    <td class="px-6 py-5 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route($isPosContext ? 'pos.cylinders.show' : 'admin.cylinders.show', $transaction) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-orange-100 text-orange-700 hover:bg-orange-200 rounded-lg transition-all font-semibold">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                View
                                            </a>

                                            @if($transaction->isActive())
                                                @if(!$isPosContext)
                                                    <a href="{{ route('admin.cylinders.edit', $transaction) }}"
                                                       class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition-all font-semibold">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                @endif

                                                <form method="POST" action="{{ route($isPosContext ? 'pos.cylinders.complete' : 'admin.cylinders.complete', $transaction) }}"
                                                      class="inline" onsubmit="return confirm('Complete this transaction?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-200 rounded-lg transition-all font-semibold">
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
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <p class="text-xl font-bold text-gray-900 mb-2">No cylinder transactions found</p>
                                            <p class="text-sm text-gray-600 mb-4">Get started by creating a new transaction</p>
                                            <a href="{{ route($createRoute) }}" class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Create Transaction
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>