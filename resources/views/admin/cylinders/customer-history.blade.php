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
                               class="flex items-center justify-center w-9 h-9 bg-gray-100 hover:bg-blue-50 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                               aria-label="Back to cylinders">
                                <svg class="w-5 h-5 text-gray-600 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                            </a>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center justify-center w-9 h-9 bg-blue-600 rounded-lg" aria-hidden="true">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-lg font-bold text-gray-900">{{ $customer->name }}</h1>
                                    <p class="text-xs text-gray-700 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ $customer->phone }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Filters & Actions -->
                        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter and export options">
                            <!-- Status Filter -->
                            <select onchange="window.location.href=this.value"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Filter by status">
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', $customer) }}"
                                        {{ !request('status') ? 'selected' : '' }}>All Status</option>
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', ['customer' => $customer, 'status' => 'active']) }}"
                                        {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', ['customer' => $customer, 'status' => 'completed']) }}"
                                        {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>

                            <!-- Transaction Type Filter -->
                            <select onchange="window.location.href=this.value"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Filter by transaction type">
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', $customer) }}"
                                        {{ !request('type') ? 'selected' : '' }}>All Types</option>
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', ['customer' => $customer, 'type' => 'drop_off']) }}"
                                        {{ request('type') == 'drop_off' ? 'selected' : '' }}>Drop-offs</option>
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', ['customer' => $customer, 'type' => 'advance_collection']) }}"
                                        {{ request('type') == 'advance_collection' ? 'selected' : '' }}>Advance Collections</option>
                            </select>

                            <!-- Payment Status Filter -->
                            <select onchange="window.location.href=this.value"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Filter by payment status">
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', $customer) }}"
                                        {{ !request('payment_status') ? 'selected' : '' }}>All Payment</option>
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', ['customer' => $customer, 'payment_status' => 'paid']) }}"
                                        {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="{{ route($isPosContext ? 'pos.cylinders.customer.history' : 'admin.cylinders.customer.history', ['customer' => $customer, 'payment_status' => 'pending']) }}"
                                        {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>

                            <!-- Divider -->
                            <div class="hidden sm:block w-px h-6 bg-gray-300" aria-hidden="true"></div>

                            <!-- Export Button -->
                            <a href="{{ route($isPosContext ? 'pos.cylinders.customer.history.export' : 'admin.cylinders.customer.history.export', ['customer' => $customer, 'status' => request('status'), 'type' => request('type'), 'payment_status' => request('payment_status')]) }}"
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

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-2">
                <!-- Total Transactions -->
                <div class="bg-white rounded-xl shadow-md p-3 border-2 border-blue-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-blue-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Total Transactions</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $stats['total_transactions'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Active Transactions -->
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-yellow-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Active</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $stats['active_transactions'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Amount Paid -->
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Total Paid</p>
                            <p class="text-lg font-bold text-green-600 truncate">KSh {{ number_format($stats['total_amount_paid'], 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pending Amount -->
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200 hover:shadow-lg transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-orange-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Pending</p>
                            <p class="text-lg font-bold text-orange-600 truncate">KSh {{ number_format($stats['total_amount_pending'], 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Cylinder Transaction History</h3>
                        </div>
                        <span class="text-sm text-gray-600 font-semibold">{{ $transactions->total() }} total</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Products</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-blue-50 transition-all duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ $transaction->reference_number }}</div>
                                        @if($transaction->isActive())
                                            @php $ageStatus = $transaction->getAgeStatus(); @endphp
                                            <div class="text-xs mt-2 px-2.5 py-1 rounded-full font-semibold {{ $ageStatus['color'] }} inline-block">
                                                {{ $ageStatus['days'] }} days waiting
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $transaction->getTransactionTypeBadgeColor() }}">
                                            {{ $transaction->isDropOff() ? 'Drop-off' : 'Advance' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        @if($transaction->items->count() > 0)
                                            <div class="text-sm space-y-2">
                                                @foreach($transaction->items as $item)
                                                    <div class="flex items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <span class="font-semibold text-gray-900">{{ $item->product->name }}</span>
                                                            @if($item->brand)
                                                                <span class="text-xs px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">{{ $item->brand }}</span>
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
                                        @if($transaction->deposit_amount > 0)
                                            <div class="text-xs text-gray-500 mt-1">+ KSh {{ number_format($transaction->deposit_amount, 0) }} deposit</div>
                                        @endif
                                        <span class="inline-flex items-center px-3 py-1.5 mt-1 rounded-full text-xs font-bold {{ $transaction->getPaymentStatusBadgeColor() }}">
                                            {{ ucfirst($transaction->payment_status) }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <div class="font-semibold text-gray-900">{{ $transaction->drop_off_date->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $transaction->drop_off_date->format('h:i A') }}</div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $transaction->getStatusBadgeColor() }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route($isPosContext ? 'pos.cylinders.show' : 'admin.cylinders.show', $transaction) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg transition-all font-semibold">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="text-xl font-bold text-gray-900 mb-2">No transactions found</p>
                                            <p class="text-sm text-gray-600 mb-4">This customer has no cylinder transactions yet</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $transactions->appends(['status' => request('status'), 'type' => request('type'), 'payment_status' => request('payment_status')])->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
