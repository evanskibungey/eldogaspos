<?php

namespace Tests\Feature\Cylinders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CylinderTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterises what actually happens to payment_status when a cylinder
 * transaction is completed.
 *
 * These tests document current behaviour, including the case where an unpaid
 * drop-off is completed and the debt then has nowhere to live. They are written
 * to fail loudly if that behaviour changes, so a fix is a deliberate act.
 */
class CylinderPaymentStatusTest extends TestCase
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

    private function cylinder(float $sizeKg = 6, int $stock = 20, float $price = 2000): Product
    {
        return Product::factory()
            ->cylinder($sizeKg)
            ->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 100000,
        ]);
    }

    private function dropOff(Product $p, Customer $c, string $paymentStatus)
    {
        return $this->actingAs($this->admin)->postJson('/admin/cylinders', [
            'transaction_type' => 'drop_off',
            'customer_id' => $c->id,
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_status' => $paymentStatus,
        ]);
    }

    private function advanceCollection(Product $p, Customer $c, string $paymentStatus, float $deposit = 0)
    {
        return $this->actingAs($this->admin)->postJson('/admin/cylinders', [
            'transaction_type' => 'advance_collection',
            'customer_id' => $c->id,
            'items' => [['product_id' => $p->id, 'quantity' => 1]],
            'payment_status' => $paymentStatus,
            'deposit_amount' => $deposit,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Drop-offs
    |--------------------------------------------------------------------------
    */

    /**
     * An unpaid drop-off records the debt only on the transaction. Unlike an
     * advance collection it never reaches customers.balance, so the customer
     * looks settled everywhere that reads the balance column.
     */
    public function test_an_unpaid_drop_off_does_not_raise_the_customer_balance(): void
    {
        $customer = $this->customer();

        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();

        $this->assertSame('pending', CylinderTransaction::first()->payment_status);
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /**
     * Handing the cylinders over is when money changes hands, so completing
     * settles the transaction. The cashier should never have to find the order
     * again afterwards just to mark it paid.
     */
    public function test_completing_a_drop_off_marks_it_paid(): void
    {
        $customer = $this->customer();
        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();

        $transaction = CylinderTransaction::first();

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        $transaction->refresh();
        $this->assertSame('completed', $transaction->status);
        $this->assertSame('paid', $transaction->payment_status);

        // A drop-off never credited the balance, so settling must not debit it.
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /**
     * The escape hatch: a customer who genuinely collects before paying.
     */
    public function test_completing_a_drop_off_can_still_defer_payment(): void
    {
        $customer = $this->customer();
        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();

        $transaction = CylinderTransaction::first();

        $this->actingAs($this->admin)
            ->post("/admin/cylinders/{$transaction->id}/complete", ['payment_status' => 'pending']);

        $transaction->refresh();
        $this->assertSame('completed', $transaction->status);
        $this->assertSame('pending', $transaction->payment_status);
    }

    public function test_completing_a_drop_off_can_mark_it_paid(): void
    {
        $customer = $this->customer();
        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();

        $transaction = CylinderTransaction::first();

        $this->actingAs($this->admin)
            ->post("/admin/cylinders/{$transaction->id}/complete", ['payment_status' => 'paid']);

        $this->assertSame('paid', $transaction->fresh()->payment_status);
        // Drop-offs never credited the balance, so clearing them must not debit it.
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /**
     * The case that used to be a dead end: completed while unpaid. It must
     * still be listed as owing, and it must be settleable.
     */
    public function test_a_completed_unpaid_drop_off_is_still_listed_and_can_be_settled(): void
    {
        $customer = $this->customer();
        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();

        $transaction = CylinderTransaction::first();

        // Deferred on purpose: the customer collected before paying.
        $this->actingAs($this->admin)
            ->post("/admin/cylinders/{$transaction->id}/complete", ['payment_status' => 'pending']);

        $this->assertSame('pending', $transaction->fresh()->payment_status);

        // Listed by the pending-payments screen even though it is completed.
        $this->actingAs($this->admin)
            ->get('/admin/cylinders/pending-payments?period=all')
            ->assertOk()
            ->assertSee($transaction->reference_number);

        // And settleable, which update() still refuses to do.
        $this->actingAs($this->admin)
            ->post("/admin/cylinders/{$transaction->id}/record-payment")
            ->assertRedirect();

        $this->assertSame('paid', $transaction->fresh()->payment_status);
        // A drop-off never credited the balance, so settling must not debit it.
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    public function test_recording_payment_on_an_advance_collection_clears_its_balance(): void
    {
        $customer = $this->customer();
        $this->advanceCollection($this->cylinder(6, 20, 2000), $customer, 'pending', 0)->assertOk();

        $this->assertEquals(2000, $customer->fresh()->balance);

        $transaction = CylinderTransaction::first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/record-payment");

        $this->assertSame('paid', $transaction->fresh()->payment_status);
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /**
     * Settling twice would debit an advance collection's balance twice and
     * push the customer into false credit.
     */
    public function test_payment_cannot_be_recorded_twice(): void
    {
        $customer = $this->customer();
        $this->advanceCollection($this->cylinder(6, 20, 2000), $customer, 'pending', 0)->assertOk();

        $transaction = CylinderTransaction::first();

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/record-payment");
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/record-payment");

        $this->assertEquals(0, $customer->fresh()->balance);
    }

    public function test_payment_cannot_be_recorded_against_a_cancelled_transaction(): void
    {
        $customer = $this->customer();
        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();

        $transaction = CylinderTransaction::first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/cancel");

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/record-payment");

        $this->assertSame('pending', $transaction->fresh()->payment_status);
    }

    public function test_bulk_marking_paid_settles_every_selected_transaction(): void
    {
        $customer = $this->customer();

        $this->dropOff($this->cylinder(), $customer, 'pending')->assertOk();
        $this->advanceCollection($this->cylinder(13, 20, 3000), $customer, 'pending', 0)->assertOk();

        $ids = CylinderTransaction::pluck('id')->all();

        $this->actingAs($this->admin)
            ->post('/admin/cylinders/bulk-update-payment-status', [
                'transaction_ids' => $ids,
                'payment_status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertSame(0, CylinderTransaction::query()->pending()->count());
        // Only the advance collection ever credited the balance.
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    public function test_bulk_marking_skips_rows_already_paid(): void
    {
        $customer = $this->customer();

        $this->advanceCollection($this->cylinder(6, 20, 2000), $customer, 'pending', 0)->assertOk();
        $transaction = CylinderTransaction::first();

        // Settle it first, then include it in a bulk run.
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/record-payment");

        $this->actingAs($this->admin)
            ->post('/admin/cylinders/bulk-update-payment-status', [
                'transaction_ids' => [$transaction->id],
                'payment_status' => 'paid',
            ])
            ->assertRedirect();

        $this->assertEquals(0, $customer->fresh()->balance, 'A second settle must not debit again.');
    }

    /*
    |--------------------------------------------------------------------------
    | Advance collections
    |--------------------------------------------------------------------------
    */

    /**
     * Advance collections do put the debt on the balance, so completion has to
     * reverse both the deposit and the unpaid gas exactly.
     */
    public function test_completing_an_unpaid_advance_collection_clears_the_balance_exactly(): void
    {
        $customer = $this->customer();

        $this->advanceCollection($this->cylinder(6, 20, 2000), $customer, 'pending', 500)->assertOk();

        // 2000 gas + 500 deposit
        $this->assertEquals(2500, $customer->fresh()->balance);

        $transaction = CylinderTransaction::first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        $this->assertSame('paid', $transaction->fresh()->payment_status);
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    public function test_completing_a_paid_advance_collection_only_refunds_the_deposit(): void
    {
        $customer = $this->customer();

        $this->advanceCollection($this->cylinder(6, 20, 2000), $customer, 'paid', 500)->assertOk();

        // Paid gas adds nothing; only the deposit is carried.
        $this->assertEquals(500, $customer->fresh()->balance);

        $transaction = CylinderTransaction::first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /**
     * Both types now honour an explicit deferral. This used to be silently
     * overridden for advance collections, which also debited the balance for a
     * payment that had not been made.
     */
    public function test_an_advance_collection_can_also_defer_payment_on_completion(): void
    {
        $customer = $this->customer();
        $this->advanceCollection($this->cylinder(6, 20, 2000), $customer, 'pending', 500)->assertOk();

        $this->assertEquals(2500, $customer->fresh()->balance);

        $transaction = CylinderTransaction::first();

        $this->actingAs($this->admin)
            ->post("/admin/cylinders/{$transaction->id}/complete", ['payment_status' => 'pending']);

        $this->assertSame('pending', $transaction->fresh()->payment_status);

        // The deposit is still refunded - the empty cylinder came back - but
        // the unpaid gas stays on the balance.
        $this->assertEquals(2000, $customer->fresh()->balance);
    }
}
