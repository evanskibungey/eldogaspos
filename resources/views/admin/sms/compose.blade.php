<!-- resources/views/admin/sms/compose.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compose Campaign</h2>
                <p class="text-sm text-gray-500 mt-0.5">Promotional and informational SMS to your customers</p>
            </div>
            <a href="{{ route('admin.sms.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Message history
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-5 pb-12">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(config('services.talksasa.driver') === 'log')
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl text-sm">
                <strong>Log driver active.</strong> Messages are written to the application log, not sent.
            </div>
        @elseif(!setting('sms_enabled', false))
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-xl text-sm">
                <strong>SMS is switched off.</strong> Enable it in System Settings before sending.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.sms.send') }}" id="campaign-form" class="space-y-5">
            @csrf

            {{--
                Step 1. The registration windows are the control itself, not a
                dropdown repeating numbers shown above it - choosing "last
                month" and seeing 473 should be one action, not two.
            --}}
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <header class="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">1</span>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">When did they join?</h3>
                        <p class="text-xs text-gray-500">Target customers by registration date</p>
                    </div>
                    <span class="text-xs text-gray-500">{{ number_format($audiences['all']) }} active customers</span>
                </header>

                <div class="p-5">
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3" id="period-cards">
                        @foreach($breakdown as $value => $row)
                            <label class="period-card relative block cursor-pointer rounded-lg border-2 p-3 transition-all
                                          {{ old('period', 'any') === $value ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }}">
                                <input type="radio" name="period" value="{{ $value }}" class="sr-only period-option"
                                    {{ old('period', 'any') === $value ? 'checked' : '' }}>
                                <span class="block text-2xl font-bold text-gray-900">{{ number_format($row['count']) }}</span>
                                <span class="block text-xs font-medium text-gray-700 mt-1 leading-tight">
                                    {{ str_replace('Joined ', '', $row['label']) }}
                                </span>
                                @if($row['range'])
                                    <span class="block text-[10px] text-gray-400 mt-1 leading-tight">{{ $row['range'] }}</span>
                                @endif
                            </label>
                        @endforeach

                        <label class="period-card relative block cursor-pointer rounded-lg border-2 p-3 transition-all
                                      {{ old('period') === 'custom' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }}">
                            <input type="radio" name="period" value="custom" class="sr-only period-option"
                                {{ old('period') === 'custom' ? 'checked' : '' }}>
                            <span class="block text-2xl font-bold text-gray-400">&#8230;</span>
                            <span class="block text-xs font-medium text-gray-700 mt-1 leading-tight">Custom range</span>
                            <span class="block text-[10px] text-gray-400 mt-1">Pick your own dates</span>
                        </label>
                    </div>

                    <div id="custom-range" class="mt-4 grid sm:grid-cols-2 gap-3 {{ old('period') === 'custom' ? '' : 'hidden' }}">
                        <div>
                            <label for="date_from" class="block text-xs font-medium text-gray-700 mb-1">From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ old('date_from') }}"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        </div>
                        <div>
                            <label for="date_to" class="block text-xs font-medium text-gray-700 mb-1">To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ old('date_to') }}"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        </div>
                        <p class="sm:col-span-2 text-xs text-gray-500">Both days are included.</p>
                    </div>

                    @if(count($monthly) > 0)
                        @php $max = max(array_column($monthly, 'total')) ?: 1; @endphp
                        <details class="mt-5 group">
                            <summary class="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 cursor-pointer hover:text-orange-700 list-none">
                                <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                Registrations month by month
                            </summary>
                            <div class="mt-3 space-y-1.5">
                                @foreach($monthly as $row)
                                    <div class="flex items-center gap-3 text-sm">
                                        <span class="w-20 text-gray-600 shrink-0">{{ $row['label'] }}</span>
                                        <span class="w-12 text-right font-semibold text-gray-900 shrink-0">{{ number_format($row['total']) }}</span>
                                        <span class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                            <span class="block bg-orange-400 h-2 rounded-full"
                                                  style="width: {{ max(2, round($row['total'] / $max * 100)) }}%"></span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </section>

            <!-- Step 2: audience -->
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <header class="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">2</span>
                    <div>
                        <h3 class="font-semibold text-gray-900">Which customers?</h3>
                        <p class="text-xs text-gray-500">Narrowed further by the dates above</p>
                    </div>
                </header>

                <div class="p-5">
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach([
                            ['all', 'All active customers', 'Everyone who can be reached', number_format($audiences['all'])],
                            ['with_balance', 'Outstanding balance', 'Owes money on credit', number_format($audiences['with_balance'])],
                            ['active_cylinders', 'Cylinders still out', 'Has not returned cylinders', number_format($audiences['active_cylinders'])],
                            ['manual', 'Specific numbers', 'Paste a list, one per line', null],
                        ] as [$value, $title, $hint, $count])
                            <label class="audience-card flex items-start gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all
                                          {{ old('audience', 'all') === $value ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-orange-300' }}">
                                <input type="radio" name="audience" value="{{ $value }}" class="sr-only audience-option"
                                    {{ old('audience', 'all') === $value ? 'checked' : '' }}>
                                <span class="flex-1 min-w-0">
                                    <span class="flex items-baseline justify-between gap-2">
                                        <span class="font-medium text-gray-900 text-sm">{{ $title }}</span>
                                        @if($count !== null)
                                            <span class="text-sm font-bold text-gray-700 shrink-0">{{ $count }}</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-gray-500 mt-0.5">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div id="manual-numbers" class="mt-4 {{ old('audience') === 'manual' ? '' : 'hidden' }}">
                        <label for="numbers" class="block text-xs font-medium text-gray-700 mb-1">Phone numbers</label>
                        <textarea name="numbers" id="numbers" rows="4"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm font-mono"
                            placeholder="0712345678&#10;0723456789">{{ old('numbers') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            Any format (07&hellip;, 01&hellip;, +254&hellip;). Numbers that are not valid Kenyan mobiles are skipped.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Step 3: message -->
            <section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <header class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">3</span>
                        <div>
                            <h3 class="font-semibold text-gray-900">Message</h3>
                            <p class="text-xs text-gray-500">Sender ID <span class="font-mono">{{ $senderId }}</span></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="template-btn px-2.5 py-1 text-xs font-medium text-orange-700 bg-orange-100 rounded-md hover:bg-orange-200"
                            data-template="Order gas on the EldoGas app and get FREE delivery to your door. Download: {link}">App promo</button>
                        <button type="button" class="template-btn px-2.5 py-1 text-xs font-medium text-orange-700 bg-orange-100 rounded-md hover:bg-orange-200"
                            data-template="Need a refill? Order on the EldoGas app for FREE same-day delivery. {link}">Refill reminder</button>
                        <button type="button" id="insert-link" class="px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">Insert app link</button>
                    </div>
                </header>

                <div class="p-5">
                    <textarea name="message" id="message" rows="4" required
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
                        placeholder="Type your message">{{ old('message') }}</textarea>

                    <div class="mt-2 flex items-center justify-between text-xs">
                        <span class="text-gray-500">An opt-out line is added automatically.</span>
                        <span class="text-gray-600">
                            <span id="char-count">0</span> chars &middot;
                            <span id="segment-count">1</span> segment(s) each
                        </span>
                    </div>

                    @if($appLink === '')
                        <p class="mt-3 text-xs text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg p-2.5">
                            No app link is set. Add one under <strong>Settings &rarr; Customer app link</strong>.
                        </p>
                    @elseif(strlen($appLink) > 40)
                        <p class="mt-3 text-xs text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg p-2.5">
                            Your app link is <strong>{{ strlen($appLink) }} characters</strong> and will push most messages
                            into a second billed segment &mdash; doubling the cost. Set it to a short link such as
                            <code>https://eldogas.ke/get</code> under Settings.
                        </p>
                    @endif

                    {{-- What actually goes out, footer and all. Hidden until there is something to show. --}}
                    <div id="final-wrap" class="mt-4 hidden">
                        <p class="text-xs font-medium text-gray-700 mb-1">
                            Sent as <span class="font-normal text-gray-500">(<span id="final-length">0</span> chars)</span>
                        </p>
                        <pre id="final-message" class="text-xs text-gray-800 whitespace-pre-wrap font-sans bg-gray-50 border border-gray-200 rounded-lg p-3"></pre>
                    </div>
                </div>
            </section>

            {{--
                The cost, stated plainly. At six thousand customers a careless
                second segment is six thousand extra messages, so this is the
                loudest thing on the page and the confirmation sits inside it.
            --}}
            <section class="bg-white rounded-xl shadow-sm border-2 border-orange-300 overflow-hidden">
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">This campaign will send</p>
                            <p class="mt-1">
                                <span id="total-messages" class="text-4xl font-bold text-gray-900">0</span>
                                <span class="text-sm font-medium text-gray-600 ml-1">billable messages</span>
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                <span id="recipient-count" class="font-semibold">0</span> recipients &times;
                                <span id="segment-count-2" class="font-semibold">1</span> segment(s)
                            </p>
                        </div>

                        <div class="text-right text-xs space-y-1 min-w-0">
                            <p id="range-label" class="font-medium text-gray-700"></p>
                            <p id="skipped-label" class="text-gray-500"></p>
                            <p id="optout-label" class="text-gray-500"></p>
                            <p id="preview-state" class="italic text-gray-400"></p>
                        </div>
                    </div>

                    <div id="cost-warning" class="mt-4 hidden text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg p-3">
                        <strong>Check this before sending.</strong>
                        <span id="cost-warning-text"></span>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                        <label for="confirm" class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="confirm" id="confirm" value="1"
                                class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>I have checked the audience and the message. Sending cannot be undone.</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.sms.index') }}"
                                class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                            <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-orange-600 rounded-lg hover:bg-orange-700">
                                Queue campaign
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>

    {{--
        Inlined rather than pushed to a 'scripts' stack: layouts/app.blade.php
        does not render @stack('scripts'), so anything pushed there is silently
        dropped.
    --}}
    <script>
        (function () {
            const PREVIEW_URL = @json(route('admin.sms.audience-preview'));
            const APP_LINK = @json($appLink);
            const LARGE_SEND = 1000;

            const message = document.getElementById('message');
            const numbers = document.getElementById('numbers');
            const manualBlock = document.getElementById('manual-numbers');
            const customRange = document.getElementById('custom-range');
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const state = document.getElementById('preview-state');
            const finalWrap = document.getElementById('final-wrap');
            const warning = document.getElementById('cost-warning');

            function setText(id, value) { document.getElementById(id).textContent = value; }

            function checked(name) {
                const el = document.querySelector('input[name="' + name + '"]:checked');
                return el ? el.value : null;
            }

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

            // Selected cards are styled here rather than with :checked, because
            // the inputs are visually hidden inside the label.
            function paintCards(selector, name) {
                document.querySelectorAll(selector).forEach(function (card) {
                    const input = card.querySelector('input[name="' + name + '"]');
                    const on = input && input.checked;
                    card.classList.toggle('border-orange-500', on);
                    card.classList.toggle('bg-orange-50', on);
                    card.classList.toggle('border-gray-200', !on);
                });
            }

            function showWarning(recipients, segments) {
                const messages = recipients * segments;
                let text = '';

                if (segments > 1 && recipients >= LARGE_SEND) {
                    text = ' This message is ' + segments + ' segments, so it costs ' + segments
                         + '× what a single-segment message would. Shortening it below 160 characters'
                         + ' would save ' + (messages - recipients).toLocaleString() + ' messages.';
                } else if (recipients >= LARGE_SEND) {
                    text = ' This goes to ' + recipients.toLocaleString() + ' people and cannot be recalled.';
                }

                warning.classList.toggle('hidden', text === '');
                setText('cost-warning-text', text);
            }

            function refreshLocal() {
                const segments = segmentsFor(message.value);
                setText('char-count', message.value.length);
                setText('segment-count', segments);
                return segments;
            }

            function refreshManual() {
                const segments = refreshLocal();
                const recipients = numbers.value.split(/[\s,;]+/).filter(Boolean).length;

                setText('recipient-count', recipients);
                setText('segment-count-2', segments);
                setText('total-messages', (recipients * segments).toLocaleString());
                ['range-label', 'skipped-label', 'optout-label'].forEach(id => setText(id, ''));
                finalWrap.classList.add('hidden');
                showWarning(recipients, segments);
                state.textContent = 'From the pasted list; invalid numbers are dropped on send.';
            }

            let pending = null;

            function refreshFromServer() {
                const period = checked('period') || 'any';
                const params = new URLSearchParams({
                    audience: checked('audience') || 'all',
                    period: period,
                    message: message.value
                });

                if (period === 'custom') {
                    if (!dateFrom.value || !dateTo.value) {
                        state.textContent = 'Choose both dates to see the audience.';
                        setText('recipient-count', 0);
                        setText('total-messages', 0);
                        return;
                    }
                    params.set('date_from', dateFrom.value);
                    params.set('date_to', dateTo.value);
                }

                state.textContent = 'Checking…';

                fetch(PREVIEW_URL + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(function (data) {
                        setText('recipient-count', Number(data.recipients).toLocaleString());
                        setText('segment-count-2', data.segments);
                        setText('total-messages', Number(data.messages).toLocaleString());
                        setText('range-label', data.range || data.period_label || '');
                        setText('skipped-label', data.skipped > 0
                            ? data.skipped.toLocaleString() + ' skipped (unusable number or duplicate)' : '');
                        setText('optout-label', data.opted_out > 0
                            ? data.opted_out.toLocaleString() + ' excluded (opted out)' : '');

                        if (data.final_message) {
                            setText('final-message', data.final_message);
                            setText('final-length', data.final_length);
                            finalWrap.classList.remove('hidden');
                        } else {
                            finalWrap.classList.add('hidden');
                        }

                        showWarning(Number(data.recipients), Number(data.segments));
                        state.textContent = '';
                    })
                    .catch(function () {
                        state.textContent = 'Could not check the audience; the count may be stale.';
                    });
            }

            function refresh() {
                const isManual = checked('audience') === 'manual';
                const period = checked('period') || 'any';

                paintCards('.audience-card', 'audience');
                paintCards('.period-card', 'period');

                manualBlock.classList.toggle('hidden', !isManual);
                customRange.classList.toggle('hidden', period !== 'custom');

                refreshLocal();

                if (isManual) { refreshManual(); return; }

                // Debounced: typing should not fire a request per keystroke.
                clearTimeout(pending);
                pending = setTimeout(refreshFromServer, 350);
            }

            document.querySelectorAll('.audience-option, .period-option')
                .forEach(el => el.addEventListener('change', refresh));
            [dateFrom, dateTo].forEach(el => el.addEventListener('change', refresh));
            message.addEventListener('input', refresh);
            numbers.addEventListener('input', refresh);

            document.querySelectorAll('.template-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    message.value = this.dataset.template.replace('{link}', APP_LINK).trim();
                    refresh();
                    message.focus();
                });
            });

            document.getElementById('insert-link').addEventListener('click', function () {
                if (!APP_LINK) return;
                message.value = (message.value.trim() + ' ' + APP_LINK).trim();
                refresh();
                message.focus();
            });

            refresh();
        })();
    </script>
</x-app-layout>
