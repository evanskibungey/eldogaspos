<!-- resources/views/admin/sms/compose.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Send Bulk SMS') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">

                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 p-4 rounded-md">
                            <ul class="list-disc list-inside text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(config('services.talksasa.driver') === 'log')
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-md text-sm">
                            <strong>Log driver active.</strong> Messages will be written to the application log,
                            not sent. Set <code>SMS_DRIVER=talksasa</code> in <code>.env</code> to send for real.
                        </div>
                    @elseif(!setting('sms_enabled', false))
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-md text-sm">
                            <strong>SMS is switched off.</strong> Enable it in System Settings before sending.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.sms.send') }}" class="space-y-6" id="campaign-form">
                        @csrf

                        <!-- Audience -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Who receives this?</h3>

                            <div class="space-y-3">
                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-md cursor-pointer hover:border-orange-400">
                                    <input type="radio" name="audience" value="all" class="mt-1 audience-option"
                                        data-count="{{ $audiences['all'] }}"
                                        {{ old('audience', 'all') === 'all' ? 'checked' : '' }}>
                                    <span class="ml-3">
                                        <span class="block font-medium text-gray-900">All active customers</span>
                                        <span class="block text-sm text-gray-500">{{ $audiences['all'] }} customers</span>
                                    </span>
                                </label>

                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-md cursor-pointer hover:border-orange-400">
                                    <input type="radio" name="audience" value="with_balance" class="mt-1 audience-option"
                                        data-count="{{ $audiences['with_balance'] }}"
                                        {{ old('audience') === 'with_balance' ? 'checked' : '' }}>
                                    <span class="ml-3">
                                        <span class="block font-medium text-gray-900">Customers with an outstanding balance</span>
                                        <span class="block text-sm text-gray-500">{{ $audiences['with_balance'] }} customers</span>
                                    </span>
                                </label>

                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-md cursor-pointer hover:border-orange-400">
                                    <input type="radio" name="audience" value="active_cylinders" class="mt-1 audience-option"
                                        data-count="{{ $audiences['active_cylinders'] }}"
                                        {{ old('audience') === 'active_cylinders' ? 'checked' : '' }}>
                                    <span class="ml-3">
                                        <span class="block font-medium text-gray-900">Customers with cylinders still out</span>
                                        <span class="block text-sm text-gray-500">{{ $audiences['active_cylinders'] }} customers</span>
                                    </span>
                                </label>

                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-md cursor-pointer hover:border-orange-400">
                                    <input type="radio" name="audience" value="manual" class="mt-1 audience-option"
                                        data-count="0"
                                        {{ old('audience') === 'manual' ? 'checked' : '' }}>
                                    <span class="ml-3">
                                        <span class="block font-medium text-gray-900">Specific numbers</span>
                                        <span class="block text-sm text-gray-500">Paste numbers, one per line</span>
                                    </span>
                                </label>
                            </div>

                            <div id="manual-numbers" class="mt-4 {{ old('audience') === 'manual' ? '' : 'hidden' }}">
                                <label for="numbers" class="block text-sm font-medium text-gray-700">Phone numbers</label>
                                <textarea name="numbers" id="numbers" rows="5"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm font-mono"
                                    placeholder="0712345678">{{ old('numbers') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    Any format is accepted (07&hellip;, 01&hellip;, +254&hellip;). Numbers that are not valid
                                    Kenyan mobile numbers are skipped rather than sent.
                                </p>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Message</h3>

                            <textarea name="message" id="message" rows="5" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
                                placeholder="Type your message">{{ old('message') }}</textarea>

                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-gray-500">
                                    Sender ID: <span class="font-mono font-medium">{{ $senderId }}</span>
                                </span>
                                <span class="text-gray-600">
                                    <span id="char-count">0</span> chars &middot;
                                    <span id="segment-count">1</span> segment(s) per recipient
                                </span>
                            </div>

                            <p class="mt-3 text-sm font-medium text-gray-900 bg-orange-50 border border-orange-200 rounded-md p-3">
                                Estimated cost: <span id="total-messages">0</span> billable messages
                                (<span id="recipient-count">0</span> recipients &times;
                                <span id="segment-count-2">1</span> segments)
                            </p>
                        </div>

                        <!-- Confirm -->
                        <div class="flex items-start">
                            <input type="checkbox" name="confirm" id="confirm" value="1" class="mt-1">
                            <label for="confirm" class="ml-2 text-sm text-gray-700">
                                I have checked the audience and the message. Sending cannot be undone.
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.sms.index') }}"
                                class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700">
                                Queue campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{--
        Inlined rather than pushed to a 'scripts' stack: layouts/app.blade.php
        does not render @stack('scripts'), so anything pushed there is silently
        dropped.
    --}}
    <script>
        (function () {
            const message = document.getElementById('message');
            const manualBlock = document.getElementById('manual-numbers');
            const numbers = document.getElementById('numbers');

            // Mirrors SmsService::segments(). A single SMS holds 160 GSM-7
            // characters (70 for unicode); once split, each part carries a
            // concatenation header and holds only 153 (67).
            function segmentsFor(text) {
                if (text.length === 0) return 1;

                const isGsm = /^[\x20-\x7E\n\r]*$/.test(text);
                const single = isGsm ? 160 : 70;
                const concatenated = isGsm ? 153 : 67;

                if (text.length <= single) return 1;
                return Math.ceil(text.length / concatenated);
            }

            function recipientCount() {
                const selected = document.querySelector('.audience-option:checked');
                if (!selected) return 0;

                if (selected.value === 'manual') {
                    return numbers.value.split(/[\s,;]+/).filter(Boolean).length;
                }

                return parseInt(selected.dataset.count, 10) || 0;
            }

            function refresh() {
                const text = message.value;
                const segments = segmentsFor(text);
                const recipients = recipientCount();

                document.getElementById('char-count').textContent = text.length;
                document.getElementById('segment-count').textContent = segments;
                document.getElementById('segment-count-2').textContent = segments;
                document.getElementById('recipient-count').textContent = recipients;
                document.getElementById('total-messages').textContent = recipients * segments;
            }

            document.querySelectorAll('.audience-option').forEach(function (option) {
                option.addEventListener('change', function () {
                    manualBlock.classList.toggle('hidden', this.value !== 'manual');
                    refresh();
                });
            });

            message.addEventListener('input', refresh);
            numbers.addEventListener('input', refresh);
            refresh();
        })();
    </script>
</x-app-layout>
