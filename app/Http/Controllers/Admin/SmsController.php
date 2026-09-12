<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\Sms\PhoneNumber;
use App\Services\Sms\SmsService;
use App\Services\Sms\TalkSasaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected SmsService $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
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
            'senderId' => setting('sms_sender_id', config('services.talksasa.sender_id')),
        ]);
    }

    /**
     * Queue a campaign to the selected audience.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'audience' => 'required|in:all,with_balance,active_cylinders,manual',
            'message' => 'required|string|max:918',
            'numbers' => 'required_if:audience,manual|nullable|string',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => 'Please confirm you want to send this campaign.',
            'message.max' => 'A message longer than 918 characters cannot be sent as a single SMS.',
        ]);

        if ($validated['audience'] === 'manual') {
            $queued = $this->sendToManualList($validated['numbers'], $validated['message']);
        } else {
            $customers = $this->audienceQuery($validated['audience'])->get();
            $queued = $this->sms->queueCampaign($customers, $validated['message']);
        }

        if ($queued === 0) {
            return back()->withInput()
                ->withErrors(['audience' => 'No sendable phone numbers in that audience. Nothing was queued.']);
        }

        Log::info('SMS campaign queued', [
            'audience' => $validated['audience'],
            'queued' => $queued,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.sms.index')
            ->with('success', "Campaign queued for {$queued} recipient(s).");
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

    private function audienceQuery(string $audience)
    {
        $query = Customer::where('status', 'active')
            // The POS walk-in placeholder is a real customer row.
            ->where('phone', '!=', PhoneNumber::WALK_IN);

        if ($audience === 'with_balance') {
            $query->where('balance', '>', 0);
        }

        if ($audience === 'active_cylinders') {
            $query->whereHas('cylinderTransactions', function ($q) {
                $q->where('status', 'active');
            });
        }

        return $query;
    }

    private function audienceCounts(): array
    {
        return [
            'all' => $this->audienceQuery('all')->count(),
            'with_balance' => $this->audienceQuery('with_balance')->count(),
            'active_cylinders' => $this->audienceQuery('active_cylinders')->count(),
        ];
    }
}
