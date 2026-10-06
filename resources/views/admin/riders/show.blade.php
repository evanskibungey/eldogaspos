<x-app-layout>
@php
    $isPosContext = str_starts_with(request()->route()->getName(), 'pos.');
    $r = fn (string $name) => ($isPosContext ? 'pos.riders.' : 'admin.riders.') . $name;
    $isOut = $rider->isOut();
@endphp
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
        <div class="max-w-7xl mx-auto px-2 sm:px-3 lg:px-4">

            <a href="{{ route($r('index')) }}" class="inline-flex items-center text-sm text-gray-600 hover:text-purple-700 font-medium mb-3">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                All riders
            </a>

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

            <!-- Rider card -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-5 mb-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-2xl font-extrabold flex-shrink-0 {{ $isOut ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700' }}">
                            {{ strtoupper(substr($rider->name, 0, 1)) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $rider->name }}</h1>
                            <p class="text-gray-600">{{ $rider->phone }}@if($rider->national_id) · ID {{ $rider->national_id }}@endif</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $rider->status === 'active' ? ($isOut ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') : 'bg-gray-200 text-gray-700' }}">
                                    {{ $rider->availability }}
                                </span>
                                @if($isOut)
                                    <span class="text-xs text-gray-600">{{ $rider->active_allocations_count }} open · {{ $rider->cylinders_held }} cylinders held</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route($r('toggle-status'), $rider) }}">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded-xl font-bold text-sm transition-all {{ $rider->status === 'active' ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-purple-600 text-white hover:bg-purple-700' }}">
                            {{ $rider->status === 'active' ? 'Mark inactive' : 'Reactivate' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- History -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Pick-up history</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cylinders</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Allocated</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Completed</th>
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
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900">KSh {{ number_format($allocation->total_amount, 0) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $allocation->allocated_at->format('M d, Y h:i A') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                        {{ $allocation->completed_at ? $allocation->completed_at->format('M d, Y h:i A') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold {{ $allocation->status_badge_color }}">
                                            {{ ucfirst($allocation->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        @if($allocation->isPending())
                                            <form method="POST" action="{{ route($r('allocations.complete'), $allocation) }}"
                                                  data-confirm
                                                  data-confirm-title="Complete order {{ $allocation->reference_number }}?"
                                                  data-confirm-message="{{ $rider->name }} has returned. The cylinders are recorded as delivered and a cash sale of KSh {{ number_format($allocation->total_amount, 2) }} is written."
                                                  data-confirm-action="Complete"
                                                  data-confirm-variant="success">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white hover:bg-green-700 rounded-lg transition-all font-semibold">Complete</button>
                                            </form>
                                        @elseif($allocation->sale_id)
                                            <a href="{{ route('pos.sales.show', $allocation->sale_id) }}" class="text-purple-700 hover:underline font-semibold">View sale</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No pick-ups yet for {{ $rider->name }}.</td>
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
</x-app-layout>
