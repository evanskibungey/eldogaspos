<?php

namespace App\Console\Commands;

use App\Models\CylinderTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupCylinderTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cylinders:cleanup {--days=7 : Mark transactions older than X days as completed} {--dry-run : Preview changes without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old active cylinder transactions as completed to clean up analytics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Searching for active transactions older than {$days} days...");
        $this->newLine();

        // Find active transactions older than specified days
        $transactions = CylinderTransaction::where('status', 'active')
            ->where('drop_off_date', '<', now()->subDays($days))
            ->orderBy('drop_off_date', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('✅ No old active transactions found. Your data is clean!');
            return Command::SUCCESS;
        }

        // Group by age
        $stats = [
            '7-14 days' => $transactions->filter(fn($t) => $t->drop_off_date < now()->subDays(7) && $t->drop_off_date >= now()->subDays(14))->count(),
            '14-30 days' => $transactions->filter(fn($t) => $t->drop_off_date < now()->subDays(14) && $t->drop_off_date >= now()->subDays(30))->count(),
            '30-60 days' => $transactions->filter(fn($t) => $t->drop_off_date < now()->subDays(30) && $t->drop_off_date >= now()->subDays(60))->count(),
            '60-90 days' => $transactions->filter(fn($t) => $t->drop_off_date < now()->subDays(60) && $t->drop_off_date >= now()->subDays(90))->count(),
            '90+ days' => $transactions->filter(fn($t) => $t->drop_off_date < now()->subDays(90))->count(),
        ];

        $this->table(
            ['Age Range', 'Count'],
            collect($stats)->map(fn($count, $range) => [$range, $count])->toArray()
        );

        $this->newLine();

        // Payment status breakdown
        $paidCount = $transactions->where('payment_status', 'paid')->count();
        $pendingCount = $transactions->where('payment_status', 'pending')->count();

        $this->info("💰 Payment Status Breakdown:");
        $this->line("   - Paid: {$paidCount}");
        $this->line("   - Pending: {$pendingCount}");
        $this->newLine();

        // Show sample transactions
        $this->info("📋 Sample transactions (oldest 10):");
        $this->table(
            ['ID', 'Reference', 'Customer', 'Days Waiting', 'Payment', 'Amount'],
            $transactions->take(10)->map(function($t) {
                return [
                    $t->id,
                    $t->reference_number,
                    $t->customer_name,
                    $t->getDaysWaiting() . ' days',
                    ucfirst($t->payment_status),
                    'KSh ' . number_format($t->amount, 0)
                ];
            })->toArray()
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn("🔸 DRY RUN MODE: No changes will be made.");
            $this->info("Found {$transactions->count()} transactions that would be marked as completed.");
            $this->newLine();
            $this->info("To actually update these transactions, run:");
            $this->comment("php artisan cylinders:cleanup --days={$days}");
            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->confirm("Mark {$transactions->count()} transactions as completed?", false)) {
            $this->info('Operation cancelled.');
            return Command::FAILURE;
        }

        // Update transactions
        $this->info('🔄 Updating transactions...');

        DB::beginTransaction();

        try {
            $progressBar = $this->output->createProgressBar($transactions->count());
            $progressBar->start();

            foreach ($transactions as $transaction) {
                $transaction->update([
                    'status' => 'completed',
                    'collection_date' => $transaction->drop_off_date->addDays(1), // Set collection date to next day
                    'completed_by' => 1, // System user
                ]);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            DB::commit();

            $this->newLine();
            $this->info("✅ Successfully marked {$transactions->count()} transactions as completed!");
            $this->info("💡 Tip: Transactions with pending payment remain unpaid but are no longer counted as active.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error updating transactions: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
