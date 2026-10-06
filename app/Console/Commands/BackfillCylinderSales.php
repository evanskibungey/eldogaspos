<?php

namespace App\Console\Commands;

use App\Models\CylinderTransaction;
use App\Services\FulfilmentSaleRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Writes the missing `sales` rows for cylinder transactions completed before
 * completion started recording them.
 *
 * Deliberately a command and not a migration. It rewrites what past reports
 * say the shop earned, which is a decision for whoever owns the books - not
 * something that should happen silently the next time somebody deploys.
 *
 *     php artisan cylinders:backfill-sales --dry-run    # show, change nothing
 *     php artisan cylinders:backfill-sales              # ask, then write
 *
 * Safe to run twice: a transaction that already points at a sale is skipped.
 */
class BackfillCylinderSales extends Command
{
    protected $signature = 'cylinders:backfill-sales
                            {--dry-run : List what would be written and exit}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Create the missing sales rows for already-completed cylinder transactions';

    public function handle(FulfilmentSaleRecorder $recorder): int
    {
        $pending = CylinderTransaction::with('items')
            ->where('status', 'completed')
            ->whereNull('sale_id')
            ->orderBy('id')
            ->get()
            ->filter(fn ($t) => $t->items->isNotEmpty());

        if ($pending->isEmpty()) {
            $this->info('Nothing to backfill - every completed transaction already has a sale.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Reference', 'Completed', 'Customer', 'Cylinders', 'Amount'],
            $pending->map(fn ($t) => [
                $t->id,
                $t->reference_number,
                optional($this->soldAt($t))->format('Y-m-d') ?? '-',
                $t->customer_name ?: 'Walk-in',
                $t->items->sum('quantity'),
                number_format($t->items->sum('subtotal'), 2),
            ])->all()
        );

        $total = $pending->sum(fn ($t) => $t->items->sum('subtotal'));

        $this->newLine();
        $this->line(sprintf(
            '%d transaction(s), %s cylinders, KSh %s.',
            $pending->count(),
            $pending->sum(fn ($t) => $t->items->sum('quantity')),
            number_format($total, 2)
        ));
        $this->warn('Each sale is dated the day the cylinders were collected, so past');
        $this->warn('reports and daily totals WILL change. Stock is not touched.');

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run - nothing was written.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Write these sales?', false)) {
            $this->info('Aborted. Nothing was written.');

            return self::SUCCESS;
        }

        $written = 0;

        foreach ($pending as $transaction) {
            DB::transaction(function () use ($transaction, $recorder, &$written) {
                // Re-read under the transaction: a concurrent completion may
                // have recorded the sale between listing and writing.
                $fresh = CylinderTransaction::with('items')->lockForUpdate()->find($transaction->id);

                if (!$fresh || $fresh->saleAlreadyRecorded()) {
                    return;
                }

                $sale = $recorder->record($fresh->saleLines(), [
                    'user_id' => $fresh->completed_by ?? $fresh->created_by,
                    'customer_id' => $fresh->customer_id ?? $recorder->walkInCustomer()->id,
                    'order_number' => $fresh->order_number,
                    'payment_status' => $fresh->payment_status,
                    'notes' => "Cylinder {$fresh->transaction_type} (Ref: {$fresh->reference_number}) - backfilled",
                    'created_at' => $this->soldAt($fresh),
                ]);

                $fresh->forceFill(['sale_id' => $sale->id])->save();
                $written++;
            });
        }

        $this->newLine();
        $this->info("Done. {$written} sale(s) written.");

        return self::SUCCESS;
    }

    /**
     * When the money was actually taken.
     *
     * A drop-off is paid when the refilled cylinders are collected; an advance
     * collection when the empty comes back. Falling back to the record's own
     * timestamps rather than today keeps the revenue on the day it happened.
     */
    private function soldAt(CylinderTransaction $transaction)
    {
        return $transaction->collection_date
            ?? $transaction->return_date
            ?? $transaction->updated_at
            ?? $transaction->created_at;
    }
}
