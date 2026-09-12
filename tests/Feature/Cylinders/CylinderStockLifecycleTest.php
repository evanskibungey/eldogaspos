<?php

namespace Tests\Feature\Cylinders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CylinderTransaction;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the cylinder stock lifecycle:
 *   drop-off            reserve on create, deduct on collection
 *   advance collection  deduct on create (the gas leaves immediately)
 *
 * and the balance arithmetic around deposits.
 */
class CylinderStockLifecycleTest extends TestCase
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

    private function cylinder(float $sizeKg, int $stock, float $price = 2000): Product
    {
        return Product::factory()
            ->cylinder($sizeKg)
            ->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function createTransaction(array $payload)
    {
        return $this->actingAs($this->admin)->postJson('/admin/cylinders', $payload);
    }

    private function dropOff(Product $product, Customer $customer, int $qty = 1, string $paymentStatus = 'paid')
    {
        return $this->createTransaction([
            'transaction_type' => 'drop_off',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'payment_status' => $paymentStatus,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 5: stock is deducted when the cylinder is collected
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function a_drop_off_reserves_stock_but_does_not_deduct_it(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        $this->dropOff($sixKg, $customer)->assertOk();

        $sixKg->refresh();

        $this->assertSame(30, $sixKg->stock, 'The cylinder is still in the yard.');
        $this->assertSame(1, $sixKg->reserved_stock);
        $this->assertSame(29, $sixKg->available_stock, 'It is no longer sellable.');

        $transaction = CylinderTransaction::latest('id')->first();
        $this->assertSame(CylinderTransaction::STOCK_RESERVED, $transaction->stock_status);

        // Nothing physically moved, so nothing belongs in the ledger yet.
        $this->assertSame(0, StockMovement::where('reference_type', 'cylinder_transaction')->count());
    }

    /** @test */
    public function collecting_a_drop_off_deducts_the_stock(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        $this->dropOff($sixKg, $customer)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();

        $this->actingAs($this->admin)
            ->post("/admin/cylinders/{$transaction->id}/complete")
            ->assertRedirect();

        $sixKg->refresh();
        $transaction->refresh();

        $this->assertSame(29, $sixKg->stock, 'Stock drops when the customer collects.');
        $this->assertSame(0, $sixKg->reserved_stock);
        $this->assertSame(29, $sixKg->available_stock);

        $this->assertSame('completed', $transaction->status);
        $this->assertSame(CylinderTransaction::STOCK_COMMITTED, $transaction->stock_status);
        $this->assertNotNull($transaction->stock_committed_at);

        // The physical departure is recorded exactly once.
        $movements = StockMovement::where('reference_type', 'cylinder_collection')->get();
        $this->assertCount(1, $movements);
        $this->assertSame('out', $movements->first()->type);
        $this->assertSame(1, $movements->first()->quantity);
    }

    /** @test */
    public function the_collection_order_number_tracks_the_physical_stock_level(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        // Three drop-offs, then collect them one at a time.
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $this->dropOff($sixKg, $customer)->assertOk();
            $ids[] = CylinderTransaction::latest('id')->first()->id;
        }

        $expected = [30, 29, 28];

        foreach ($ids as $index => $id) {
            $this->actingAs($this->admin)->post("/admin/cylinders/{$id}/complete");

            $this->assertSame(
                $expected[$index],
                CylinderTransaction::find($id)->order_number,
                "Collection {$index} should be numbered {$expected[$index]}."
            );
        }

        $this->assertSame(27, $sixKg->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 6: no double deduction
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function completing_twice_does_not_deduct_twice(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        $this->dropOff($sixKg, $customer, 2)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");
        $this->assertSame(28, $sixKg->fresh()->stock);

        // A second completion - double submit, retried request, impatient user.
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        $this->assertSame(28, $sixKg->fresh()->stock, 'Stock must not move a second time.');
        $this->assertSame(
            1,
            StockMovement::where('reference_type', 'cylinder_collection')->count(),
            'Only one collection movement should exist.'
        );
    }

    /** @test */
    public function an_advance_collection_deducts_immediately_and_not_again_on_completion(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        $this->createTransaction([
            'transaction_type' => 'advance_collection',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $sixKg->id, 'quantity' => 1]],
            'payment_status' => 'paid',
            'deposit_amount' => 500,
        ])->assertOk();

        $sixKg->refresh();

        // The gas physically left the yard at creation.
        $this->assertSame(29, $sixKg->stock);
        $this->assertSame(0, $sixKg->reserved_stock);

        $transaction = CylinderTransaction::latest('id')->first();
        $this->assertSame(CylinderTransaction::STOCK_COMMITTED, $transaction->stock_status);
        $this->assertSame(30, $transaction->order_number);

        // Completing means the empty came back - stock must not move again.
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        $this->assertSame(29, $sixKg->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancellation and deletion
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function cancelling_an_uncollected_drop_off_releases_the_reservation(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        $this->dropOff($sixKg, $customer, 2)->assertOk();
        $transaction = CylinderTransaction::latest('id')->first();

        $this->assertSame(28, $sixKg->fresh()->available_stock);

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/cancel");

        $sixKg->refresh();

        $this->assertSame(30, $sixKg->stock, 'Physical stock never changed.');
        $this->assertSame(0, $sixKg->reserved_stock);
        $this->assertSame(30, $sixKg->available_stock);
        $this->assertSame(
            CylinderTransaction::STOCK_RELEASED,
            CylinderTransaction::find($transaction->id)->stock_status
        );
    }

    /** @test */
    public function cancelling_a_committed_advance_collection_restores_the_stock(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $customer = Customer::factory()->create();

        $this->createTransaction([
            'transaction_type' => 'advance_collection',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $sixKg->id, 'quantity' => 1]],
            'payment_status' => 'paid',
            'deposit_amount' => 0,
        ])->assertOk();

        $this->assertSame(29, $sixKg->fresh()->stock);

        $transaction = CylinderTransaction::latest('id')->first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/cancel");

        $this->assertSame(30, $sixKg->fresh()->stock, 'A committed deduction is restored.');
    }

    /*
    |--------------------------------------------------------------------------
    | Deposit arithmetic (previously H-04 / H-05)
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function a_paid_advance_collection_with_a_deposit_settles_back_to_zero(): void
    {
        $sixKg = $this->cylinder(6, 30, 1000);
        $customer = Customer::factory()->create(['balance' => 0]);

        $this->createTransaction([
            'transaction_type' => 'advance_collection',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $sixKg->id, 'quantity' => 1]],
            'payment_status' => 'paid',
            'deposit_amount' => 2000,
        ])->assertOk();

        // The deposit obligation stands until the empty comes back.
        $this->assertEquals(2000, $customer->fresh()->balance);

        $transaction = CylinderTransaction::latest('id')->first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        // Previously this landed on -2000, because the deposit was refunded
        // without ever having been raised.
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /** @test */
    public function a_pending_advance_collection_settles_back_to_zero_on_completion(): void
    {
        $sixKg = $this->cylinder(6, 30, 1000);
        $customer = Customer::factory()->create(['balance' => 0]);

        $this->createTransaction([
            'transaction_type' => 'advance_collection',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $sixKg->id, 'quantity' => 1]],
            'payment_status' => 'pending',
            'deposit_amount' => 2000,
        ])->assertOk();

        // Owes the gas plus the deposit.
        $this->assertEquals(3000, $customer->fresh()->balance);

        $transaction = CylinderTransaction::latest('id')->first();
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete");

        $this->assertEquals(0, $customer->fresh()->balance);
        $this->assertSame('paid', CylinderTransaction::find($transaction->id)->payment_status);
    }

    /** @test */
    public function cancelling_after_payment_does_not_strand_the_deposit(): void
    {
        $sixKg = $this->cylinder(6, 30, 1000);
        $customer = Customer::factory()->create(['balance' => 0]);

        $this->createTransaction([
            'transaction_type' => 'advance_collection',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $sixKg->id, 'quantity' => 1]],
            'payment_status' => 'pending',
            'deposit_amount' => 2000,
        ])->assertOk();

        $transaction = CylinderTransaction::latest('id')->first();
        $this->assertEquals(3000, $customer->fresh()->balance);

        // Customer settles the gas.
        $this->actingAs($this->admin)->put("/admin/cylinders/{$transaction->id}", [
            'payment_status' => 'paid',
        ]);
        $this->assertEquals(2000, $customer->fresh()->balance);

        // Then the transaction is cancelled.
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/cancel");

        // Previously the 2000 deposit stayed on the account forever.
        $this->assertEquals(0, $customer->fresh()->balance);
    }

    /*
    |--------------------------------------------------------------------------
    | Availability
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function a_drop_off_cannot_reserve_more_than_is_available(): void
    {
        $sixKg = $this->cylinder(6, 2);
        $customer = Customer::factory()->create();

        $this->dropOff($sixKg, $customer, 5)->assertStatus(422);

        $sixKg->refresh();

        $this->assertSame(2, $sixKg->stock);
        $this->assertSame(0, $sixKg->reserved_stock);
        $this->assertSame(0, CylinderTransaction::count(), 'Nothing should have been created.');
    }

    /** @test */
    public function reserved_units_cannot_be_reserved_again(): void
    {
        $sixKg = $this->cylinder(6, 2);
        $customer = Customer::factory()->create();

        $this->dropOff($sixKg, $customer, 2)->assertOk();
        $this->assertSame(0, $sixKg->fresh()->available_stock);

        // Everything is spoken for.
        $this->dropOff($sixKg, $customer, 1)->assertStatus(422);

        $this->assertSame(2, $sixKg->fresh()->reserved_stock);
        $this->assertSame(1, CylinderTransaction::count());
    }
}
