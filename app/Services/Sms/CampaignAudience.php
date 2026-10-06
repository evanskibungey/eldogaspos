<?php

namespace App\Services\Sms;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Who a campaign goes to.
 *
 * Kept out of the controller because deciding the audience is the expensive
 * part of sending: every recipient is billed, per segment, and a filter that
 * quietly matches the whole customer base costs real money. The same rules
 * have to drive the preview an admin sees and the send that follows, or the
 * number they approved is not the number they paid for.
 */
class CampaignAudience
{
    public const PERIOD_ANY = 'any';
    public const PERIOD_THIS_MONTH = 'this_month';
    public const PERIOD_LAST_MONTH = 'last_month';
    public const PERIOD_LAST_3_MONTHS = 'last_3_months';
    public const PERIOD_LAST_4_MONTHS = 'last_4_months';
    public const PERIOD_CUSTOM = 'custom';

    public const AUDIENCES = ['all', 'with_balance', 'active_cylinders', 'manual'];

    /**
     * Labels are here rather than in the view so the preview, the confirmation
     * and the log all describe an audience the same way.
     */
    public static function periods(): array
    {
        return [
            self::PERIOD_ANY => 'Any time',
            self::PERIOD_THIS_MONTH => 'Joined this month',
            self::PERIOD_LAST_MONTH => 'Joined last month',
            self::PERIOD_LAST_3_MONTHS => 'Joined in the last 3 months',
            self::PERIOD_LAST_4_MONTHS => 'Joined in the last 4 months',
            self::PERIOD_CUSTOM => 'Custom date range',
        ];
    }

    public static function periodLabel(string $period): string
    {
        return self::periods()[$period] ?? $period;
    }

    /**
     * The window a period covers, or null for "any time".
     *
     * "This month" and "last month" are CALENDAR months - an admin asking for
     * last month means the month on the wall, not the last 30 days. The 3- and
     * 4-month options are rolling windows, because "within the last 3 months"
     * reads as a span back from today.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function dateRange(string $period, ?string $from = null, ?string $to = null): ?array
    {
        switch ($period) {
            case self::PERIOD_THIS_MONTH:
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];

            case self::PERIOD_LAST_MONTH:
                $previous = Carbon::now()->subMonthNoOverflow();

                return [$previous->copy()->startOfMonth(), $previous->copy()->endOfMonth()];

            case self::PERIOD_LAST_3_MONTHS:
                return [Carbon::now()->subMonthsNoOverflow(3)->startOfDay(), Carbon::now()->endOfDay()];

            case self::PERIOD_LAST_4_MONTHS:
                return [Carbon::now()->subMonthsNoOverflow(4)->startOfDay(), Carbon::now()->endOfDay()];

            case self::PERIOD_CUSTOM:
                if (empty($from) || empty($to)) {
                    return null;
                }

                $start = Carbon::parse($from)->startOfDay();
                $end = Carbon::parse($to)->endOfDay();

                // Tolerate the dates being entered the wrong way round rather
                // than silently matching nobody.
                return $start->lessThanOrEqualTo($end) ? [$start, $end] : [$end->startOfDay(), $start->endOfDay()];

            case self::PERIOD_ANY:
            default:
                return null;
        }
    }

    /**
     * Customers matching an audience and a registration window.
     */
    public function query(string $audience, string $period = self::PERIOD_ANY, ?string $from = null, ?string $to = null)
    {
        // marketable(), not selectable(): anyone who has asked to stop hearing
        // from us is out of every campaign, permanently and without needing to
        // be remembered by whoever took the call.
        $query = Customer::marketable();

        if ($audience === 'with_balance') {
            $query->where('balance', '>', 0);
        }

        if ($audience === 'active_cylinders') {
            $query->whereHas('cylinderTransactions', fn ($q) => $q->where('status', 'active'));
        }

        $range = self::dateRange($period, $from, $to);

        if ($range !== null) {
            $query->whereBetween('created_at', $range);
        }

        return $query;
    }

    /**
     * The customers a send would actually reach.
     *
     * Two things are removed that a raw count would include:
     *
     *  - numbers the gateway cannot dial. A malformed number is still billed
     *    as a failed send, so it is dropped rather than attempted.
     *  - the same person twice. `customers.phone` is free text, so one person
     *    can exist as "0712…" and "+254712…". Both would be charged, and they
     *    would receive the campaign twice. The oldest record wins.
     *
     * @return Collection<int, Customer>
     */
    public function resolve(string $audience, string $period = self::PERIOD_ANY, ?string $from = null, ?string $to = null): Collection
    {
        return $this->query($audience, $period, $from, $to)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->filter(fn (Customer $c) => PhoneNumber::isSendable($c->phone))
            ->unique(fn (Customer $c) => PhoneNumber::normalise($c->phone))
            ->values();
    }

    /**
     * What a send would cost, before committing to it.
     *
     * `matched` is how many customer records the filter found; `recipients` is
     * how many messages actually go out. The gap between them is the point of
     * this method - an admin who sees only "matched" budgets for the wrong
     * number.
     */
    public function summarise(
        string $audience,
        string $period = self::PERIOD_ANY,
        ?string $from = null,
        ?string $to = null,
        string $message = ''
    ): array {
        $matched = $this->query($audience, $period, $from, $to)->count();
        $recipients = $this->resolve($audience, $period, $from, $to);
        $reachable = $recipients->count();

        $segments = $message === '' ? 0 : SmsService::segments($message);

        return [
            'matched' => $matched,
            'recipients' => $reachable,
            'skipped' => max(0, $matched - $reachable),
            // Surfaced separately so a shrinking audience is explained rather
            // than looking like a bug in the filter.
            'opted_out' => $this->optedOutCount($audience, $period, $from, $to),
            'segments' => $segments,
            'messages' => $reachable * $segments,
            'period_label' => self::periodLabel($period),
            'range' => self::formatRange($period, $from, $to),
        ];
    }

    /**
     * How the customer base breaks down by when people joined, so the filters
     * can be chosen against real numbers rather than guessed at.
     */
    public function breakdown(): array
    {
        $counts = [];

        foreach (array_keys(self::periods()) as $period) {
            if ($period === self::PERIOD_CUSTOM) {
                continue;
            }

            $counts[$period] = [
                'label' => self::periodLabel($period),
                'count' => $this->query('all', $period)->count(),
                'range' => self::formatRange($period),
            ];
        }

        return $counts;
    }

    /**
     * Registrations per calendar month, newest first - the "analyse the
     * customer base" view. Capped so a long-lived shop does not render a
     * hundred rows.
     */
    public function monthlyRegistrations(int $months = 12): array
    {
        $since = Carbon::now()->subMonthsNoOverflow($months - 1)->startOfMonth();

        return Customer::selectable()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderByDesc('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'label' => Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * How many customers this filter would have reached but for an opt-out.
     */
    public function optedOutCount(string $audience, string $period = self::PERIOD_ANY, ?string $from = null, ?string $to = null): int
    {
        $query = Customer::selectable()->where('sms_opt_out', true);

        if ($audience === 'with_balance') {
            $query->where('balance', '>', 0);
        }

        if ($audience === 'active_cylinders') {
            $query->whereHas('cylinderTransactions', fn ($q) => $q->where('status', 'active'));
        }

        $range = self::dateRange($period, $from, $to);

        if ($range !== null) {
            $query->whereBetween('created_at', $range);
        }

        return $query->count();
    }

    /**
     * The campaign message as it will actually be sent.
     *
     * Two lines are appended here rather than left to whoever writes the
     * campaign, so neither can be forgotten: the app link, because every
     * campaign is ultimately trying to move people onto the app, and the
     * opt-out line, because marketing has to carry a way out of it.
     *
     * Both are added centrally and both are counted in the segment estimate,
     * because they are billed like every other character.
     *
     * Each is skipped when the message already contains it - the ready-made
     * templates put the link inline ("Download: <link>"), and an admin may
     * write their own STOP wording. Neither should end up doubled.
     */
    public static function withFooter(string $message): string
    {
        $out = rtrim($message);

        $link = trim((string) setting('sms_app_link', ''));

        if ($link !== '' && strpos($out, $link) === false) {
            $out .= "\n" . $link;
        }

        $optOut = trim((string) setting('sms_opt_out_footer', 'Reply STOP to opt out.'));

        if ($optOut !== '' && stripos($out, 'STOP') === false) {
            $out .= "\n" . $optOut;
        }

        return $out;
    }

    public static function formatRange(string $period, ?string $from = null, ?string $to = null): ?string
    {
        $range = self::dateRange($period, $from, $to);

        if ($range === null) {
            return null;
        }

        return $range[0]->format('d M Y') . ' - ' . $range[1]->format('d M Y');
    }
}
