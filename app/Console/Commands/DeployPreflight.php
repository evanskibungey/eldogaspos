<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only audit to run against a database BEFORE applying the stock-control
 * migrations. Reports exactly what they will touch and flags the cases that
 * cannot be resolved automatically.
 *
 * Writes nothing. Safe to run on production.
 */
class DeployPreflight extends Command
{
    protected $signature = 'deploy:preflight';

    protected $description = 'Audit this database for the stock-control upgrade (read-only, writes nothing)';

    private int $blockers = 0;
    private int $warnings = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>Stock-control upgrade preflight</>');
        $this->line('  database: <fg=cyan>' . DB::connection()->getDatabaseName() . '</>');
        $this->line('  ' . str_repeat('-', 66));

        $alreadyApplied = Schema::hasColumn('products', 'reserved_stock');

        if ($alreadyApplied) {
            $this->warn('  These migrations appear to have run already on this database.');
            $this->line('  Re-running the audit is still safe; findings below reflect current state.');
        }

        $this->auditCylinderSizes();
        $this->auditSalesStatusEnum();
        $this->auditCylinderTransactions();
        $this->auditAdvanceCollectionDeposits();
        $this->auditStockSanity();

        $this->newLine();
        $this->line('  ' . str_repeat('-', 66));

        if ($this->blockers > 0) {
            $this->error("  {$this->blockers} item(s) need a decision before you migrate.");
            $this->line('  Nothing has been changed. Resolve the items above, then re-run.');

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->warn("  {$this->warnings} advisory item(s). Review, then migrate when happy.");

            return self::SUCCESS;
        }

        $this->info('  Clear. The migrations are safe to apply to this database.');

        return self::SUCCESS;
    }

    /**
     * Which products will be recognised as cylinders.
     *
     * Loads the rule straight out of the migration file so the audit and the
     * migration can never drift apart - if you change one, the other follows.
     */
    private function auditCylinderSizes(): void
    {
        $this->section('Cylinder size detection');

        $migrationPath = database_path(
            'migrations/2026_08_12_000003_backfill_product_cylinder_sizes.php'
        );

        if (!file_exists($migrationPath)) {
            $this->fail('Migration 2026_08_12_000003 is missing.', 'Upload the full package before running this audit.');

            return;
        }

        $rule = require $migrationPath;

        $products = DB::table('products')->get(['id', 'name']);
        $cylinders = [];
        $accessories = [];

        foreach ($products as $product) {
            $size = $rule->resolveSizeKg($product->name);

            if ($size !== null) {
                $cylinders[] = ['name' => $product->name, 'size' => $size];
            } else {
                $accessories[] = $product->name;
            }
        }

        $this->line('    detection is by product name, with accessory keywords excluded');
        $this->line('    cylinders:     <fg=green>' . count($cylinders) . '</>');

        // Group by size so the operator can sanity-check the sizes they expect.
        $bySize = [];
        foreach ($cylinders as $c) {
            $bySize[(string) $c['size']][] = $c['name'];
        }
        ksort($bySize, SORT_NUMERIC);

        foreach ($bySize as $size => $names) {
            $label = rtrim(rtrim(number_format((float) $size, 2, '.', ''), '0'), '.') . 'kg';
            $this->line('      ' . str_pad($label, 9) . implode(', ', $names));
        }

        $this->line('    not cylinders: ' . count($accessories)
            . ($accessories ? '  (' . implode(', ', array_slice($accessories, 0, 10)) . ')' : ''));

        if (empty($cylinders)) {
            $this->fail(
                'No product name carries a kg figure, so nothing would be treated as a cylinder.',
                'Order numbers would fall back to the first cart line. Check your product naming '
                . 'before migrating. Existing products: '
                . DB::table('products')->pluck('name')->take(10)->implode(', ')
            );

            return;
        }

        // Anything holding stock that was classed as an accessory is worth a look,
        // in case a genuine cylinder is named in a way the rule does not catch.
        $stocked = DB::table('products')
            ->whereIn('name', $accessories)
            ->where('stock', '>', 0)
            ->pluck('name');

        if ($stocked->isNotEmpty()) {
            $this->warnItem(
                'These carry stock but are not treated as cylinders: ' . $stocked->implode(', ')
                . '. That is correct for regulators, hoses and grills. If any of them is really a '
                . 'cylinder, set its size by hand under Products after deploying.'
            );
        }
    }

    /**
     * Rows that would be truncated by the enum change.
     */
    private function auditSalesStatusEnum(): void
    {
        $this->section('Sales status enum');

        $statuses = DB::table('sales')->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->get();

        if ($statuses->isEmpty()) {
            $this->line('    no sales rows');

            return;
        }

        foreach ($statuses as $row) {
            $label = $row->status === '' ? "'' (empty - a previously failed void)" : $row->status;
            $this->line('    ' . str_pad($label, 22) . $row->c);
        }

        $empty = $statuses->firstWhere('status', '');

        if ($empty) {
            $this->warnItem(
                $empty->c . " sale(s) have an empty status. That is the fingerprint of a void that failed "
                . 'under a non-strict MySQL setting. They currently count towards revenue. '
                . "After migrating, decide whether they should become 'voided' (and have their stock returned) "
                . "or 'completed'."
            );
        }
    }

    /**
     * How the stock_status backfill will classify existing transactions.
     */
    private function auditCylinderTransactions(): void
    {
        $this->section('Cylinder transactions');

        if (!Schema::hasTable('cylinder_transactions')) {
            $this->line('    table not present');

            return;
        }

        $total = DB::table('cylinder_transactions')->count();

        if ($total === 0) {
            $this->line('    none - nothing to backfill');

            return;
        }

        $rows = DB::table('cylinder_transactions')
            ->select('status', 'transaction_type', DB::raw('COUNT(*) c'))
            ->groupBy('status', 'transaction_type')
            ->get();

        foreach ($rows as $row) {
            $target = in_array($row->status, ['active', 'completed'], true) ? 'committed' : 'released';
            $this->line(
                '    ' . str_pad($row->status . ' / ' . $row->transaction_type, 34)
                . $row->c . '  ->  stock_status=' . $target
            );
        }

        $active = DB::table('cylinder_transactions')->where('status', 'active')->count();

        if ($active > 0) {
            $this->line('');
            $this->line("    <fg=cyan>{$active} active transaction(s) will be marked 'committed'.</>");
            $this->line('    That is deliberate: under the old code their stock was already deducted at');
            $this->line('    creation, so marking them committed stops completion deducting a second time.');
            $this->line('    They will carry no reservation, which matches what actually happened.');
        }
    }

    /**
     * The one case the migration cannot resolve on its own.
     */
    private function auditAdvanceCollectionDeposits(): void
    {
        $this->section('Advance-collection deposits');

        if (!Schema::hasTable('cylinder_transactions')) {
            $this->line('    table not present');

            return;
        }

        $affected = DB::table('cylinder_transactions')
            ->where('status', 'active')
            ->where('transaction_type', 'advance_collection')
            ->where('payment_status', 'paid')
            ->where('deposit_amount', '>', 0)
            ->get(['id', 'reference_number', 'customer_id', 'customer_name', 'deposit_amount']);

        if ($affected->isEmpty()) {
            $this->line('    <fg=green>none affected</>');

            return;
        }

        $this->fail(
            $affected->count() . ' active advance collection(s) are marked paid and carry a deposit.',
            "The old code raised a customer's balance only when an advance collection was PENDING, "
            . 'but always refunded the deposit on completion - that was the negative-balance bug. '
            . 'The new code raises the deposit at creation instead, so completing one of these legacy rows '
            . "would decrement a deposit that was never added, driving the balance negative.\n\n"
            . "      These rows cannot be classified automatically: a row created 'paid' never had the deposit "
            . "added, while one created 'pending' and later switched to 'paid' did. Both look identical now.\n\n"
            . '      Affected rows:'
        );

        foreach ($affected as $row) {
            $this->line(
                '        ' . str_pad($row->reference_number, 18)
                . str_pad($row->customer_name, 24)
                . 'deposit ' . number_format($row->deposit_amount, 2)
            );
        }

        $this->line('');
        $this->line('      For each one, check whether the deposit is already on the customer balance.');
        $this->line('      If it is NOT, add it before completing the transaction, using');
        $this->line('      Customers -> Adjust balance. Or simply complete these few by hand');
        $this->line('      under the old rules and correct the balance afterwards.');
    }

    private function auditStockSanity(): void
    {
        $this->section('Stock sanity');

        $negative = DB::table('products')->where('stock', '<', 0)->count();

        if ($negative > 0) {
            $this->warnItem("{$negative} product(s) have negative stock. Correct them before deploying, "
                . 'because availability checks treat anything at or below zero as unsellable.');
        } else {
            $this->line('    no negative stock');
        }

        $this->line('    products: ' . DB::table('products')->count()
            . '   sales: ' . DB::table('sales')->count()
            . '   stock movements: ' . DB::table('stock_movements')->count());
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('  <options=bold>' . $title . '</>');
    }

    private function warnItem(string $message): void
    {
        $this->warnings++;
        $this->line('    <fg=yellow>WARN</>  ' . wordwrap($message, 92, "\n          "));
    }

    private function fail(string $headline, string $detail): void
    {
        $this->blockers++;
        $this->line('    <fg=red>NEEDS DECISION</>  ' . $headline);
        $this->line('      ' . wordwrap($detail, 92, "\n      "));
    }
}
