<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
        <div class="max-w-7xl mx-auto px-2 sm:px-3 lg:px-4">
@php
    $currentRoute = request()->route()->getName();
    $isPosContext = str_starts_with($currentRoute, 'pos.');
    $indexRoute = $isPosContext ? 'pos.cylinders.index' : 'admin.cylinders.index';
@endphp

            <!-- Enhanced Header with Icon -->
            <div class="mb-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="{{ route($indexRoute) }}" class="flex items-center justify-center w-12 h-12 bg-white hover:bg-gray-100 rounded-xl shadow-md transition-all">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </a>
                        <div class="flex items-center justify-center w-16 h-16 bg-green-500 rounded-2xl shadow-lg">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Paid Drop-offs</h1>
                            <p class="text-gray-600 mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Cylinders dropped off with payment received
                            </p>
                        </div>
                    </div>

                    <!-- Period Selector -->
                    <div class="flex gap-2">
                        <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs', ['period' => 'daily']) }}"
                           class="px-4 py-2 rounded-lg font-semibold transition-all {{ $period === 'daily' ? 'bg-green-600 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                            Daily
                        </a>
                        <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs', ['period' => 'weekly']) }}"
                           class="px-4 py-2 rounded-lg font-semibold transition-all {{ $period === 'weekly' ? 'bg-green-600 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                            Weekly
                        </a>
                        <a href="{{ route($isPosContext ? 'pos.cylinders.paid-drop-offs' : 'admin.cylinders.paid-drop-offs', ['period' => 'monthly']) }}"
                           class="px-4 py-2 rounded-lg font-semibold transition-all {{ $period === 'monthly' ? 'bg-green-600 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                            Monthly
                        </a>
                    </div>
                </div>
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
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m0 0l7-7 7 7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide truncate">Total Dropped</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $stats['total_cylinders_dropped'] }}</p>
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
                                        <div class="text-xs mt-2 px-2.5 py-1 rounded-full font-semibold {{ $ageStatus['color'] }} inline-block">
                                            {{ $ageStatus['days'] }} days waiting
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">{{ $transaction->customer_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $transaction->customer_phone }}</div>
                                            </div>
                                        </div>
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
                                                      class="inline" onsubmit="return confirm('Complete this transaction?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg transition-all font-semibold">
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
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
