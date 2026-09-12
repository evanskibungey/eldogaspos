<?php

namespace Tests\Unit;

use App\Services\Sms\SmsService;
use PHPUnit\Framework\TestCase;

/**
 * The item list in a receipt SMS is variable-length text inside a fixed
 * character budget, which is exactly where costs escape unnoticed.
 *
 * These exercise the sizing rule directly, without a database, so a long
 * product name or a large cart is proven safe whatever the catalogue holds.
 * The two template helpers mirror the live messages; the synthetic ones exist
 * to force the degradation steps that the real templates are too roomy to hit.
 */
class ReceiptMessageItemsTest extends TestCase
{
    /** The budget the live messages are sized against (two billed parts). */
    private const LIMIT = 306;

    /**
     * Mirrors ReceiptMessage::fitItems + itemsSummary. Kept here as the
     * specification of the rule: as much detail as fits, degrading to a
     * truncated list and finally a bare count.
     */
    private function fit(array $lines, string $template, int $limit): string
    {
        $parts = [];
        $totalQuantity = 0;

        foreach ($lines as [$name, $quantity]) {
            $totalQuantity += $quantity;
            $parts[] = $quantity > 1 ? "{$quantity}x {$name}" : $name;
        }

        $budget = max(10, $limit - mb_strlen(str_replace('{items}', '', $template)));
        $full = implode(', ', $parts);

        $summary = $full;

        if (mb_strlen($full) > $budget) {
            $summary = $totalQuantity . ' ' . ($totalQuantity === 1 ? 'item' : 'items');

            for ($keep = count($parts) - 1; $keep >= 1; $keep--) {
                $candidate = implode(', ', array_slice($parts, 0, $keep))
                    . ' +' . (count($parts) - $keep) . ' more';

                if (mb_strlen($candidate) <= $budget) {
                    $summary = $candidate;
                    break;
                }
            }
        }

        return str_replace('{items}', $summary, $template);
    }

    /**
     * Mirrors the live drop-off message in ReceiptMessage::cylinderCreated().
     * No company header - the sender ID already reads ELDOGAS.
     */
    private function receiptTemplate(): string
    {
        return "Drop-off: {items}\nKSh 3,500.00\nPayment: PENDING"
            . "\nRef CYL20260812001 to collect. Did you know you can order using the EldoGas App"
            . "\nItishaTunaDeliver, Asante.\nhttps://eldogas.co.ke/app";
    }

    /** Mirrors the live POS sale receipt in ReceiptMessage::forSale(). */
    private function saleTemplate(): string
    {
        return "{items}\nKSh 3,500.00\nCash\nItishaTunaDeliver, Asante.\nhttps://eldogas.co.ke/app";
    }

    private function longCart(): array
    {
        return [
            ['Pressure Regulator (Low Pressure)', 2],
            ['Cylinder Valve (Quick Connect)', 1],
            ['Double Burner Gas Cooktop', 1],
            ['Fire Extinguisher (2kg)', 4],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | The live messages
    |--------------------------------------------------------------------------
    */

    public function test_the_drop_off_receipt_names_a_single_item(): void
    {
        $message = $this->fit([['13kg Gas Cylinder', 1]], $this->receiptTemplate(), self::LIMIT);

        $this->assertStringContainsString('13kg Gas Cylinder', $message);
    }

    public function test_a_quantity_above_one_is_shown(): void
    {
        $message = $this->fit([['13kg Gas Cylinder', 3]], $this->receiptTemplate(), self::LIMIT);

        $this->assertStringContainsString('3x 13kg Gas Cylinder', $message);
    }

    /**
     * The drop-off receipt carries the collection reference and the app pitch
     * as well as the items, so it costs two billed messages. Pinned as a
     * number: it is sent on every drop-off, so a careless addition here is
     * expensive, and a third part must never appear.
     */
    public function test_the_drop_off_receipt_costs_two_parts_and_never_three(): void
    {
        $message = $this->fit([['13kg Gas Cylinder', 1]], $this->receiptTemplate(), self::LIMIT);

        $this->assertSame(
            2,
            SmsService::segments($message),
            'Drop-off receipt is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * The longest real product name in the catalogue is 33 characters.
     */
    public function test_the_longest_product_name_is_still_named(): void
    {
        $message = $this->fit([['Pressure Regulator (Low Pressure)', 1]], $this->receiptTemplate(), self::LIMIT);

        $this->assertStringContainsString('Pressure Regulator (Low Pressure)', $message);
        $this->assertLessThanOrEqual(2, SmsService::segments($message), $message);
    }

    /**
     * The sale receipt has no reference line or pitch, so even a full cart of
     * long names is spelled out rather than truncated.
     */
    public function test_the_sale_receipt_names_every_item_in_a_long_cart(): void
    {
        $message = $this->fit($this->longCart(), $this->saleTemplate(), self::LIMIT);

        $this->assertStringContainsString('2x Pressure Regulator (Low Pressure)', $message);
        $this->assertStringContainsString('4x Fire Extinguisher (2kg)', $message);
        $this->assertStringNotContainsString('more', $message);
        $this->assertLessThanOrEqual(2, SmsService::segments($message), $message);
    }

    /*
    |--------------------------------------------------------------------------
    | The degradation steps
    |--------------------------------------------------------------------------
    |
    | Synthetic templates, sized to force each fallback. The live templates are
    | too roomy to reach these, but the rule still has to hold if message copy
    | grows or a very long product name is added.
    */

    public function test_a_tight_budget_truncates_with_a_count_of_the_rest(): void
    {
        $template = "{items}\n" . str_repeat('x', 100);

        $message = $this->fit($this->longCart(), $template, 160);

        $this->assertStringContainsString('2x Pressure Regulator (Low Pressure)', $message);
        $this->assertStringContainsString('+3 more', $message);
        $this->assertSame(1, SmsService::segments($message), $message);
    }

    /**
     * When not even one name fits, the customer still learns how much they got.
     */
    public function test_a_very_tight_budget_falls_back_to_a_bare_count(): void
    {
        $template = "{items}\n" . str_repeat('x', 130);

        $message = $this->fit([
            ['Pressure Regulator (Low Pressure)', 2],
            ['Cylinder Valve (Quick Connect)', 3],
        ], $template, 160);

        $this->assertStringContainsString('5 items', $message);
    }

    public function test_one_item_reads_as_singular(): void
    {
        $template = "{items}\n" . str_repeat('x', 130);

        $message = $this->fit([['Pressure Regulator (Low Pressure)', 1]], $template, 160);

        $this->assertStringContainsString('1 item', $message);
        $this->assertStringNotContainsString('1 items', $message);
    }
}
