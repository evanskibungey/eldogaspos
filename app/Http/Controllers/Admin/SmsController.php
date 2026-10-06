<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\Sms\CampaignAudience;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\SmsService;
use App\Services\Sms\TalkSasaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected SmsService $sms;
    protected CampaignAudience $audience;

    public function __construct(SmsService $sms, CampaignAudience $audience)
    {
        $this->sms = $sms;
        $this->audience = $audience;
    }

    /**
     * Message history with delivery outcomes.
     */
    public function index(Request $request)
    {
        $query = SmsLog::with('customer');

        if ($request->filled('purpose')) {
            $query->where('purpose', $request->purpose);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        $stats = [
            'total' => SmsLog::count(),
            'sent' => SmsLog::sent()->count(),
            'failed' => SmsLog::failed()->count(),
            'today' => SmsLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.sms.index', compact('logs', 'stats'));
    }

    /**
     * Compose a campaign. Audience counts are shown up front because each
     * recipient is billed, and an unnoticed audience of 4,000 is expensive.
     */
    public function compose()
    {
        return view('admin.sms.compose', [
            'audiences' => $this->audienceCounts(),
            'periods' => CampaignAudience::periods(),
            'breakdown' => $this->audience->breakdown(),
            'monthly' => $this->audience->monthlyRegistrations(),
            'appLink' => trim((string) setting('sms_app_link', '')),
            'senderId' => setting('sms_sender_id', config('services.talksasa.sender_id')),
        ]);
    }

    /**
     * What the current filter would cost, without sending anything.
     *
     * The audience is resolved with exactly the rules send() uses, so the
     * number an admin approves is the number they are billed for. Answering
     * this from the same service - rather than counting separately here - is
     * what keeps the two from drifting.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'audience' => 'required|in:' . implode(',', CampaignAudience::AUDIENCES),
            'period' => 'nullable|in:' . implode(',', array_keys(CampaignAudience::periods())),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'message' => 'nullable|string|max:918',
        ]);

        // A pasted list has no registration date to filter on.
        if ($validated['audience'] === 'manual') {
            return response()->json([
                'matched' => null,
                'recipients' => null,
                'skipped' => 0,
                'segments' => SmsService::segments($validated['message'] ?? ''),
                'messages' => null,
                'period_label' => null,
                'range' => null,
                'manual' => true,
            ]);
        }

        // Estimate against the message as it will be SENT - footer included -
        // or the segment count understates what gets billed.
        $message = $validated['message'] ?? '';
        $final = $message === '' ? '' : CampaignAudience::withFooter($message);

        $summary = $this->audience->summarise(
            $validated['audience'],
            $validated['period'] ?? CampaignAudience::PERIOD_ANY,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
            $final
        );

        $summary['final_message'] = $final;
        $summary['final_length'] = mb_strlen($final);

        return response()->json($summary);
    }

    /**
     * Queue a campaign to the selected audience.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'audience' => 'required|in:' . implode(',', CampaignAudience::AUDIENCES),
            'period' => 'nullable|in:' . implode(',', array_keys(CampaignAudience::periods())),
            'date_from' => 'required_if:period,custom|nullable|date',
            'date_to' => 'required_if:period,custom|nullable|date',
            'message' => 'required|string|max:918',
            'numbers' => 'required_if:audience,manual|nullable|string',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Please confirm you want to send this campaign.',
            'message.max' => 'A message longer than 918 characters cannot be sent as a single SMS.',
            'date_from.required_if' => 'Choose the date the range starts.',
            'date_to.required_if' => 'Choose the date the range ends.',
        ]);

        $period = $validated['period'] ?? CampaignAudience::PERIOD_ANY;

        // The app link and the opt-out line are appended centrally, so neither
        // can be left off a campaign by accident.
        $validated['message'] = CampaignAudience::withFooter($validated['message']);

        if ($validated['audience'] === 'manual') {
            $queued = $this->sendToManualList($validated['numbers'], $validated['message']);
            $descriptor = 'a pasted list';
        } else {
            // Resolved, not just queried: unsendable numbers are dropped and
            // one person held under two spellings is messaged once. Whatever
            // the preview showed is what goes out.
            $customers = $this->audience->resolve(
                $validated['audience'],
                $period,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            );

            $queued = $this->sms->queueCampaign($customers, $validated['message']);
            $descriptor = $validated['audience'] . ', ' . CampaignAudience::periodLabel($period);
        }

        if ($queued === 0) {
            return back()->withInput()
                ->withErrors(['audience' => 'No sendable phone numbers matched that filter. Nothing was queued.']);
        }

        Log::info('SMS campaign queued', [
            'audience' => $validated['audience'],
            'period' => $period,
            'range' => CampaignAudience::formatRange(
                $period,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            ),
            'segments' => SmsService::segments($validated['message']),
            'queued' => $queued,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.sms.index')
            ->with('success', "Campaign queued for {$queued} recipient(s) ({$descriptor}).");
    }

    /**
     * Remaining credit, straight from the gateway. Surfaced on demand rather
     * than on every page load so a slow gateway never blocks the UI.
     */
    public function balance(TalkSasaClient $client)
    {
        if (config('services.talksasa.driver') === 'log') {
            return response()->json([
                'success' => false,
                'message' => 'SMS is running on the log driver; no live balance.',
            ]);
        }

        $balance = $client->balance();

        return response()->json([
            'success' => $balance !== null,
            'data' => $balance,
            'message' => $balance === null ? 'Could not read balance from the gateway.' : null,
        ]);
    }

    /**
     * Honour or reverse a marketing opt-out for one customer.
     *
     * Staff take these requests on the phone and at the counter far more often
     * than by text, so this is the path that actually gets used.
     */
    public function toggleOptOut(Request $request, \App\Models\Customer $customer)
    {
        if ($customer->sms_opt_out) {
            $customer->optInToSms();
            $message = "{$customer->name} will receive campaigns again.";
        } else {
            $customer->optOutOfSms('admin');
            $message = "{$customer->name} has been opted out of marketing SMS.";
        }

        Log::info('SMS marketing preference changed', [
            'customer_id' => $customer->id,
            'opted_out' => $customer->fresh()->sms_opt_out,
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'sms_opt_out' => $customer->fresh()->sms_opt_out,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Inbound messages from the gateway, so a customer texting STOP is honoured
     * without anyone having to read it.
     *
     * Unauthenticated because the gateway cannot log in; it is protected by the
     * secret in the URL, which is why that secret must be set before the
     * callback is configured. Anything that is not a stop word is acknowledged
     * and ignored - this is not an inbox.
     *
     * TalkSasa has to be pointed at this URL for it to ever run. Until it is,
     * opt-outs happen through the admin toggle above.
     */
    public function inbound(Request $request, string $secret)
    {
        $expected = (string) config('services.talksasa.inbound_secret');

        if ($expected === '' || !hash_equals($expected, $secret)) {
            abort(404);
        }

        // Gateways disagree about field names, so accept the usual spellings
        // rather than depending on one vendor's shape.
        $from = $request->input('from')
            ?? $request->input('sender')
            ?? $request->input('msisdn')
            ?? $request->input('phone');

        $text = trim((string) ($request->input('message') ?? $request->input('text') ?? $request->input('body')));

        $isStop = in_array(strtoupper(preg_replace('/[^A-Za-z]/', '', $text)), [
            'STOP', 'UNSUBSCRIBE', 'OPTOUT', 'CANCEL', 'END', 'QUIT',
        ], true);

        if (!$isStop || !PhoneNumber::isSendable($from)) {
            return response()->json(['success' => true, 'action' => 'ignored']);
        }

        // One person can be on file under several spellings of the same number;
        // every one of them has to stop.
        $customers = \App\Models\Customer::withPhone($from)->get();
        $changed = 0;

        foreach ($customers as $customer) {
            if ($customer->optOutOfSms('sms')) {
                $changed++;
            }
        }

        Log::info('Inbound STOP processed', [
            'from' => PhoneNumber::normalise($from),
            'matched_customers' => $customers->count(),
            'newly_opted_out' => $changed,
        ]);

        return response()->json([
            'success' => true,
            'action' => 'opted_out',
            'matched' => $customers->count(),
        ]);
    }

    /**
     * Pasted numbers, one per line or comma separated. Unparseable entries are
     * dropped rather than sent, since a malformed number is still billed.
     */
    private function sendToManualList(?string $raw, string $message): int
    {
        $queued = 0;

        foreach (preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $number) {
            if (!PhoneNumber::isSendable($number)) {
                continue;
            }

            if ($this->sms->queue($number, $message, SmsLog::PURPOSE_CAMPAIGN) !== null) {
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Headline counts for the audience radios, before any date filter is
     * applied. The live preview narrows these once a period is chosen.
     */
    private function audienceCounts(): array
    {
        return [
            'all' => $this->audience->query('all')->count(),
            'with_balance' => $this->audience->query('with_balance')->count(),
            'active_cylinders' => $this->audience->query('active_cylinders')->count(),
        ];
    }
}
