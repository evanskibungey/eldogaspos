<?php

namespace App\Services\Sms;

use App\Models\CylinderTransaction;
use App\Models\Sale;

/**
 * Builds the customer-facing text for outbound messages.
 *
 * Kept separate from sending so the wording can be changed and tested without
 * touching the gateway. Every message is written to fit one 160-character
 * segment where possible - a second segment doubles the cost of every message
 * the business ever sends, so the tests assert segment counts rather than
 * leaving it to chance.
 */
class ReceiptMessage
{
    /**
     * Used only when the settings table is unreachable. The configured
     * `receipt_footer` is the real source, and it is shared with printed
     * receipts so paper and SMS carry the same sign-off.
     */
    public const DEFAULT_FOOTER = 'ItishaTunaDeliver, Asante.';

    /**
     * Past 160 characters a message is split, and each part then holds 153 -
     * so two parts is 306. Every message budgets its item list against this:
     * naming the goods is the content, so it is never the thing dropped to save
     * a part. What a message actually costs depends on how long the configured
     * app link is.
     */
    private const TWO_SEGMENTS = 306;

    public static function forSale(Sale $sale): string
    {
        $currency = setting('currency_symbol', 'KSh');
        $total = number_format((float) $sale->total_amount, 2);

        // No company line: the sender ID already reads ELDOGAS, so repeating it
        // in the body spends 12 billed characters saying nothing new. Those
        // characters go to the product name instead, which is the part the
        // customer cannot get anywhere else.
        //
        // The items also replace the receipt number - they are holding the
        // printed receipt if they need the reference.
        $message = "{items}\n"
            . "{$currency} {$total}\n"
            . ucfirst((string) $sale->payment_method);

        if ($sale->payment_method === 'credit' && $sale->customer) {
            $balance = number_format((float) $sale->customer->balance, 2);
            $message .= "\nBalance: {$currency} {$balance}";
        }

        $message .= "\n" . self::footer() . self::linkLine();

        // Two parts, for the same reason as the cylinder receipt: the item name
        // is the content, so it is not the thing that gets dropped to save a
        // part. Typical carts on a short link still bill as one.
        return self::fitItems($message, $sale->items, self::TWO_SEGMENTS);
    }

    /**
     * Sent when a cylinder transaction is opened.
     *
     * This is the customer's record of what they left with us and what they
     * owe, so it stays factual - the reference is what they quote on
     * collection.
     */
    public static function cylinderCreated(CylinderTransaction $transaction): string
    {
        $currency = setting('currency_symbol', 'KSh');
        $total = number_format((float) $transaction->getTotalAmount(), 2);

        // No company header, and "Cylinder" dropped from the label: the sender
        // ID already reads ELDOGAS, and this is the tightest message in the
        // system. A new drop-off has no order number yet, so it carries the
        // 14-character reference as well as the items, the footer and the app
        // link. Those reclaimed characters are what let it name the product
        // instead of falling back to "1 item".
        $type = $transaction->isDropOff() ? 'Drop-off' : 'Collection';

        $message = "{$type}: {items}\n"
            . "{$currency} {$total}";

        if ($transaction->isPending()) {
            $message .= "\nPayment: PENDING";
        }

        // The one message that must still carry the reference: it is the token
        // the customer quotes to collect their cylinders. Naming the items
        // instead would leave them nothing to present at the counter.
        if ($transaction->isDropOff() && $transaction->isActive()) {
            // The app pitch rides on the reference line rather than getting its
            // own, so it costs only the words themselves. It appears only here,
            // on an active drop-off: a returning advance collection is closing
            // out, not waiting to collect.
            $message .= "\nRef {$transaction->display_number} to collect."
                . " Did you know you can order using the EldoGas App";
        }

        $message .= "\n" . self::footer() . self::linkLine();

        // Budgeted against two parts, not one. Naming what the customer left
        // with us is the point of the message; with a long app link the fixed
        // content alone already exceeds a single part, so budgeting to 160
        // bought nothing back and cost the product name. A short link still
        // lands this in one part - see the tests.
        return self::fitItems($message, $transaction->items, self::TWO_SEGMENTS);
    }

    /**
     * Sent when the transaction closes: thanks, plus the one moment the
     * customer is most receptive to hearing they need not come in next time.
     *
     * The sender ID already reads ELDOGAS, so the company name is left out -
     * those characters buy the delivery offer instead.
     */
    public static function cylinderCompleted(CylinderTransaction $transaction): string
    {
        $name = self::firstName($transaction->customer_name);
        $greeting = $name === '' ? 'Thank you!' : "Thank you {$name}!";

        // The two transaction types close in opposite directions: a drop-off
        // ends when the customer collects their refilled cylinders, an advance
        // collection ends when they bring the empties back. "Complete" was
        // vague for both; saying which way the cylinders moved is what confirms
        // to the customer that the right thing happened.
        $verb = $transaction->isDropOff() ? 'collected' : 'returned';

        $message = "{$greeting} {items} - {$verb}.";

        $promo = self::appPromo();
        if ($promo !== '') {
            $message .= "\n" . $promo;
        }

        return self::fitItems($message, $transaction->items, self::TWO_SEGMENTS);
    }

    /**
     * Sent when money is recorded against a transaction that was settled later.
     *
     * This one deliberately exceeds a single segment: the receipt, the offer
     * and the app link do not fit in 160 characters, and the link was worth the
     * second part. Kept under 306 so it never becomes a third.
     */
    public static function paymentReceived(CylinderTransaction $transaction): string
    {
        $currency = setting('currency_symbol', 'KSh');
        $total = number_format((float) $transaction->getTotalAmount(), 2);

        $message = "Payment received: {$currency} {$total} for {items}."
            . "\nThank you for refilling your gas with us. Order gas online & get FREE home delivery and earn points."
            . self::linkLine();

        return self::fitItems($message, $transaction->items, self::TWO_SEGMENTS);
    }

    /**
     * The online-ordering pitch and app link, or an empty string when no link
     * is configured.
     */
    private static function appPromo(): string
    {
        $link = self::appLink();

        if ($link === '') {
            return '';
        }

        return "Did you know ? You can order gas using the EldoGas App and earn points, with FREE home delivery. Download the app now!\n{$link}";
    }

    /**
     * The customer app link, configurable so store URLs and campaign tags can
     * change without a deploy. Every character here is billed on every message
     * that carries it.
     */
    private static function appLink(): string
    {
        return trim((string) setting('sms_app_link', ''));
    }

    /**
     * First name only, so the greeting stays personal without a long full name
     * pushing the message into a second billed segment.
     */
    private static function firstName(?string $fullName): string
    {
        $first = trim(explode(' ', trim((string) $fullName))[0] ?? '');

        if ($first === '' || strcasecmp($first, 'walk-in') === 0) {
            return '';
        }

        return mb_substr($first, 0, 15);
    }

    /**
     * Falls back to the receipt footer already configured for printed receipts,
     * so SMS and paper say the same thing.
     */
    private static function footer(): string
    {
        return (string) setting('receipt_footer', self::DEFAULT_FOOTER);
    }

    /**
     * What was bought, named, within a character budget.
     *
     * Customers recognise "2x 13kg Gas Cylinder"; they do not recognise
     * "#CYL20260812001". Product names run to 33 characters here and a cart can
     * hold several, so this is capped: an SMS that spills past 160 characters
     * costs twice as much, and it would do so on the busiest messages.
     *
     * Degrades in steps - full list, then a truncated list with a "+N more"
     * tail, then a bare count - so something meaningful survives at any budget.
     *
     * @param iterable $items Line items exposing `product` and `quantity`.
     */
    private static function itemsSummary($items, int $budget): string
    {
        $parts = [];
        $totalQuantity = 0;

        foreach ($items as $item) {
            $name = trim((string) (optional($item->product)->name ?? ''));
            $quantity = (int) $item->quantity;
            $totalQuantity += $quantity;

            if ($name === '') {
                continue;
            }

            $parts[] = $quantity > 1 ? "{$quantity}x {$name}" : $name;
        }

        if ($parts === []) {
            return $totalQuantity > 0 ? self::itemCount($totalQuantity) : '';
        }

        $full = implode(', ', $parts);

        if (mb_strlen($full) <= $budget) {
            return $full;
        }

        // Keep whichever leading items fit, and say how many were left out.
        for ($keep = count($parts) - 1; $keep >= 1; $keep--) {
            $remaining = count($parts) - $keep;
            $candidate = implode(', ', array_slice($parts, 0, $keep)) . " +{$remaining} more";

            if (mb_strlen($candidate) <= $budget) {
                return $candidate;
            }
        }

        return self::itemCount($totalQuantity);
    }

    private static function itemCount(int $quantity): string
    {
        return $quantity . ' ' . ($quantity === 1 ? 'item' : 'items');
    }

    /**
     * Fit the item list into whatever room the rest of the message leaves.
     *
     * A fixed budget cannot work: the company name, footer and app link are all
     * configurable, so the space left over changes without any code change.
     * Measuring the finished message and giving the items the remainder keeps
     * it inside `$limit` however those settings are edited, and shows as much
     * detail as will fit rather than a guessed truncation.
     *
     * @param string $template Message text containing a single {items} marker.
     * @param int    $limit    Character ceiling: 160 for a one-part message,
     *                         306 for a two-part one.
     */
    private static function fitItems(string $template, $items, int $limit): string
    {
        $withoutItems = str_replace('{items}', '', $template);

        // Never squeeze below something recognisable like "2 items".
        $budget = max(10, $limit - mb_strlen($withoutItems));

        return str_replace('{items}', self::itemsSummary($items, $budget), $template);
    }

    /**
     * The app link on its own line, or an empty string when none is set.
     */
    private static function linkLine(): string
    {
        $link = self::appLink();

        return $link === '' ? '' : "\n" . $link;
    }
}
