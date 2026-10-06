<x-app-layout>
@php
    // Reachable under /pos for cashiers and /admin for admins, like cylinder
    // management. Every link on the page stays inside whichever one we are in.
    $isPosContext = str_starts_with(request()->route()->getName(), 'pos.');
    $r = fn (string $name) => ($isPosContext ? 'pos.riders.' : 'admin.riders.') . $name;
@endphp
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
        <div class="max-w-7xl mx-auto px-2 sm:px-3 lg:px-4">

            <!-- Header -->
            <div class="mb-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-16 h-16 bg-purple-600 rounded-2xl shadow-lg">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Rider Cylinder Management</h1>
                            <p class="text-gray-600 mt-1">Cylinders booked out to delivery riders, and the orders waiting to be closed when they return</p>
                        </div>
                    </div>
                    <a href="{{ route('pos.dashboard') }}"
                       class="inline-flex items-center justify-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Pick-up from POS
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-3 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-800 font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Riders available</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['riders_available'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Riders out</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['riders_out'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Open orders</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $stats['open_allocations'] }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-md p-3 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cylinders out</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['cylinders_out'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

                <!-- Riders -->
                <div class="space-y-3">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-bold text-gray-900">Riders</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse($riders as $rider)
                                <div class="px-4 py-3 flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold flex-shrink-0 {{ $rider->isOut() ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700' }}">
                                        {{ strtoupper(substr($rider->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route($r('show'), $rider) }}" class="text-sm font-bold text-gray-900 hover:text-purple-700 truncate block">{{ $rider->name }}</a>
                                        <div class="text-xs text-gray-500">{{ $rider->phone }}</div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $rider->isOut() ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $rider->availability }}
                                        </span>
                                        @if($rider->isOut())
                                            <div class="text-xs text-gray-500 mt-1">{{ $rider->active_allocations_count }} open</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-gray-500">No riders yet. Add one below.</div>
                            @endforelse
                        </div>

                        @if($inactiveRiders->isNotEmpty())
                            <details class="border-t border-gray-200">
                                <summary class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-50">
                                    Inactive ({{ $inactiveRiders->count() }})
                                </summary>
                                <div class="divide-y divide-gray-100">
                                    @foreach($inactiveRiders as $rider)
                                        <div class="px-4 py-2 flex items-center gap-3 text-sm">
                                            <div class="flex-1 min-w-0">
                                                <span class="font-semibold text-gray-700">{{ $rider->name }}</span>
                                                <span class="text-xs text-gray-500 ml-2">{{ $rider->phone }}</span>
                                            </div>
                                            <form method="POST" action="{{ route($r('toggle-status'), $rider) }}">
                                                @csrf
                                                <button class="text-xs font-bold text-purple-700 hover:underline">Reactivate</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>

                    <!-- Add rider -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-5">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">Add a rider</h3>
                        <form method="POST" action="{{ route($r('store')) }}" class="space-y-3">
                            @csrf
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Full name"
                                   class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="Phone (07xx or 2547xx)"
                                   class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <input type="text" name="national_id" value="{{ old('national_id') }}" placeholder="National ID (optional)"
                                   class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <button type="submit" class="w-full px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition-all">
                                Add rider
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Allocations -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ request('status') ? ucfirst(request('status')) . ' orders' : 'Open orders' }}
                        </h3>
                        <form method="GET" action="{{ route($r('index')) }}" class="flex items-center gap-2">
                            <select name="status" onchange="this.form.submit()"
                                    class="px-3 py-2 border-2 border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">Open</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Rider</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cylinders</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Allocated</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($allocations as $allocation)
                                    <tr class="hover:bg-purple-50 transition-all duration-150">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-bold text-gray-900">{{ $allocation->reference_number }}</div>
                                            <div class="text-xs text-gray-500 mt-1">by {{ optional($allocation->user)->name ?? '—' }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route($r('show'), $allocation->rider) }}" class="text-sm font-semibold text-gray-900 hover:text-purple-700">{{ $allocation->rider->name }}</a>
                                            <div class="text-xs text-gray-500">{{ $allocation->rider->phone }}</div>
                                            @if($allocation->customer_phone)
                                                <div class="text-xs text-purple-700 font-semibold mt-1">
                                                    &rarr; {{ \App\Services\Sms\PhoneNumber::local($allocation->customer_phone) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm space-y-1">
                                                @foreach($allocation->items as $item)
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="font-semibold text-gray-900">{{ optional($item->product)->name ?? 'Product #' . $item->product_id }}</span>
                                                        <span class="text-xs px-2.5 py-1 bg-gray-200 text-gray-700 rounded-full font-bold flex-shrink-0">×{{ $item->quantity }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900">
                                            KSh {{ number_format($allocation->total_amount, 0) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <div class="font-semibold text-gray-900">{{ $allocation->allocated_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $allocation->allocated_at->format('h:i A') }} · {{ $allocation->allocated_at->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $allocation->status_badge_color }}">
                                                {{ ucfirst($allocation->status) }}
                                            </span>
                                            @if($allocation->isCompleted() && $allocation->completed_at)
                                                <div class="text-xs text-gray-500 mt-1">{{ $allocation->completed_at->format('M d, h:i A') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                            @if($allocation->isPending())
                                                <div class="flex items-center gap-2">
                                                    <form method="POST" action="{{ route($r('allocations.complete'), $allocation) }}"
                                                          data-confirm
                                                          data-confirm-title="Complete order {{ $allocation->reference_number }}?"
                                                          data-confirm-message="{{ $allocation->rider->name }} has returned. The cylinders are recorded as delivered and a cash sale of KSh {{ number_format($allocation->total_amount, 2) }} is written."
                                                          data-confirm-action="Complete"
                                                          data-confirm-variant="success">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white hover:bg-green-700 rounded-lg transition-all font-semibold">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Complete
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route($r('allocations.cancel'), $allocation) }}"
                                                          data-confirm
                                                          data-confirm-title="Cancel {{ $allocation->reference_number }}?"
                                                          data-confirm-message="The cylinders go back into sellable stock and no sale is recorded. This cannot be undone."
                                                          data-confirm-action="Cancel order"
                                                          data-confirm-variant="danger">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg transition-all font-semibold">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif($allocation->sale_id)
                                                <a href="{{ route('pos.sales.show', $allocation->sale_id) }}" class="text-purple-700 hover:underline font-semibold">View sale</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                            @if(request('status'))
                                                No {{ request('status') }} orders.
                                            @else
                                                Nothing is out with a rider right now. Use <strong>Pick-up</strong> on a product in the POS to allocate.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($allocations->hasPages())
                        <div class="px-5 py-3 border-t border-gray-200">
                            {{ $allocations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
