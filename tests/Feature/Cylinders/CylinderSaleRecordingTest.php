<?php

namespace Tests\Feature\Cylinders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CylinderTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Completing a cylinder transaction records a sale.
 *
 * It takes money and moves stock, but used to write no `sales` row - so the
 * refill side of the business, about half the shop's takings, was invisible to
 * the POS sales badge, the admin Sales Overview and every cashier report.
 *
 * The tests that matter most here are the ones proving the stock is NOT moved
 * twice: the revenue is recorded alongside a deduction that already happened.
 */
class CylinderSaleRecordingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $cylinders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();
    }

    private function cylinder(float $sizeKg = 6, int $stock = 30, float $price = 2000): Product
    {
        return Product::factory()->cylinder($sizeKg)->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function dropOff(Product $product, Customer $customer, int $qty = 1, string $paymentStatus = 'paid')
    {
        return $this->actingAs($this->admin)->postJson('/admin/cylinders', [
            'transaction_type' => 'drop_off',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'payment_status' => $paymentStatus,
        ]);
    }

    private function complete(CylinderTransaction $t, array $payload = [])
    {
        return $this->actingAs($this->admin)->post("/admin/cylinders/{$t->id}/complete", $payload);
    }

    /*
    |--------------------------------------------------------------------------
    | The sale
    |--------------------------------------------------------------------------
    */

    public function test_completing_a_drop_off_records_a_sale(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $customer = Customer::factory()->create();

        $this->dropOff($product, $customer, 2)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();

        $this->assertSame(0, Sale::count(), 'No sale until the cylinders are collected.');

        $this->complete($transaction);

        $sale = Sale::first();
        $this->assertNotNull($sale, 'Completing must record a sale.');
        $this->assertSame($sale->id, $transaction->fresh()->sale_id);
        $this->assertEquals(4000, $sale->total_amount);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame($customer->id, $sale->customer_id);

        $item = SaleItem::where('sale_id', $sale->id)->first();
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame(2, $item->quantity);
        $this->assertEquals(4000, $item->subtotal);
    }

    /**
     * The whole point of the change: the badge and the Sales Overview both
     * count `sales`, so a refill has to land there to be seen.
     */
    public function test_the_refill_now_counts_towards_todays_sales(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $customer = Customer::factory()->create();

        $this->dropOff($product, $customer, 1)->assertOk();
        $this->complete(CylinderTransaction::latest('id')->first());

        $this->assertSame(1, Sale::whereDate('created_at', today())->notVoided()->count());
        $this->assertEquals(2000, Sale::whereDate('created_at', today())->notVoided()->sum('total_amount'));
    }

    /*
    |--------------------------------------------------------------------------
    | Stock must not move twice
    |--------------------------------------------------------------------------
    */

    public function test_recording_the_sale_does_not_deduct_the_stock_again(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $customer = Customer::factory()->create();

        $this->dropOff($product, $customer, 3)->assertOk();
        $this->complete(CylinderTransaction::latest('id')->first());

        $product->refresh();
        $this->assertSame(27, (int) $product->stock, 'Exactly the 3 cylinders collected.');
        $this->assertSame(0, (int) $product->reserved_stock);

        // One movement, from the collection - not a second one from the sale.
        $this->assertSame(1, StockMovement::where('reference_type', 'cylinder_collection')->count());
        $this->assertSame(0, StockMovement::where('reference_type', 'sale')->count());
    }

    public function test_completing_twice_records_only_one_sale(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $customer = Customer::factory()->create();

        $this->dropOff($product, $customer, 2)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();

        $this->complete($transaction);
        $this->complete($transaction);

        $this->assertSame(1, Sale::count(), 'The same money must not be booked twice.');
        $this->assertSame(28, (int) $product->fresh()->stock);
    }

    public function test_an_unpaid_collection_records_an_unpaid_sale(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $customer = Customer::factory()->create();

        $this->dropOff($product, $customer, 1, 'pending')->assertOk();
        $this->complete(CylinderTransaction::latest('id')->first(), ['payment_status' => 'pending']);

        $this->assertSame('pending', Sale::first()->payment_status);
    }

    public function test_cancelling_records_no_sale(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $customer = Customer::factory()->create();

        $this->dropOff($product, $customer, 2)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/cancel");

        $this->assertSame(0, Sale::count());
        $this->assertNull($transaction->fresh()->sale_id);
        $this->assertSame(30, (int) $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | The backfill command
    |--------------------------------------------------------------------------
    */

    /** A transaction completed before this feature existed. */
    private function legacyCompleted(Product $product, Customer $customer, int $qty = 1): CylinderTransaction
    {
        $this->dropOff($product, $customer, $qty)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();
        $this->complete($transaction);

        // Unpick the sale, leaving the row as an older release would have.
        $transaction->refresh();
        Sale::find($transaction->sale_id)?->delete();
        $transaction->forceFill(['sale_id' => null])->save();

        return $transaction->fresh();
    }

    public function test_the_backfill_dry_run_changes_nothing(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $this->legacyCompleted($product, Customer::factory()->create(), 2);

        $this->artisan('cylinders:backfill-sales --dry-run')->assertSuccessful();

        $this->assertSame(0, Sale::count(), 'A dry run must not write anything.');
    }

    public function test_the_backfill_writes_the_missing_sale_on_the_day_it_happened(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $transaction = $this->legacyCompleted($product, Customer::factory()->create(), 2);

        $collectedOn = now()->subDays(9)->startOfDay();
        $transaction->forceFill(['collection_date' => $collectedOn])->save();

        $this->artisan('cylinders:backfill-sales --force')->assertSuccessful();

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(4000, $sale->total_amount);
        $this->assertSame(
            $collectedOn->toDateString(),
            $sale->created_at->toDateString(),
            'Revenue must land on the day it was taken, not today.'
        );
        $this->assertSame($sale->id, $transaction->fresh()->sale_id);

        // Backfilling is revenue only - it must never move stock.
        $this->assertSame(28, (int) $product->fresh()->stock);
    }

    public function test_the_backfill_is_safe_to_run_twice(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $this->legacyCompleted($product, Customer::factory()->create(), 1);

        $this->artisan('cylinders:backfill-sales --force')->assertSuccessful();
        $this->artisan('cylinders:backfill-sales --force')->assertSuccessful();

        $this->assertSame(1, Sale::count());
    }

    public function test_the_backfill_leaves_active_transactions_alone(): void
    {
        $product = $this->cylinder(6, 30, 2000);
        $this->dropOff($product, Customer::factory()->create(), 1)->assertOk();

        $this->artisan('cylinders:backfill-sales --force')->assertSuccessful();

        $this->assertSame(0, Sale::count(), 'Nothing has been collected yet.');
    }
}
