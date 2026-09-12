<!-- resources/views/admin/sms/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('SMS History') }}
            </h2>
            <a href="{{ route('admin.sms.compose') }}"
                class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">
                Send bulk SMS
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            @if(config('services.talksasa.driver') === 'log')
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-md text-sm">
                    <strong>Log driver active.</strong> Messages below were written to the application log,
                    not delivered to handsets.
                </div>
            @endif

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Sent</div>
                    <div class="text-2xl font-bold text-green-600">{{ number_format($stats['sent']) }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Failed</div>
                    <div class="text-2xl font-bold text-red-600">{{ number_format($stats['failed']) }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">Today</div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['today']) }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('admin.sms.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search number or message"
                        class="rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">

                    <select name="purpose" class="rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                        <option value="">All types</option>
                        <option value="sale_receipt" {{ request('purpose') === 'sale_receipt' ? 'selected' : '' }}>Sale receipt</option>
                        <option value="cylinder_receipt" {{ request('purpose') === 'cylinder_receipt' ? 'selected' : '' }}>Cylinder receipt</option>
                        <option value="cylinder_thank_you" {{ request('purpose') === 'cylinder_thank_you' ? 'selected' : '' }}>Thank you</option>
                        <option value="payment_received" {{ request('purpose') === 'payment_received' ? 'selected' : '' }}>Payment received</option>
                        <option value="campaign" {{ request('purpose') === 'campaign' ? 'selected' : '' }}>Campaign</option>
                    </select>

                    <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                        <option value="">All statuses</option>
                        <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-900">
                            Filter
                        </button>
                        <a href="{{ route('admin.sms.index') }}"
                            class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Log table -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $log->created_at->format('d M, H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        <div class="font-mono">{{ $log->recipient }}</div>
                                        @if($log->customer)
                                            <div class="text-xs text-gray-500">{{ $log->customer->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                        {{ str_replace('_', ' ', ucfirst($log->purpose)) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 max-w-md">
                                        <div class="truncate" title="{{ $log->message }}">{{ $log->message }}</div>
                                        <div class="text-xs text-gray-400">{{ $log->segments }} segment(s)</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        @if($log->status === 'sent')
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Sent</span>
                                        @elseif($log->status === 'failed')
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"
                                                title="{{ $log->error }}">Failed</span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Queued</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                        No messages yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
