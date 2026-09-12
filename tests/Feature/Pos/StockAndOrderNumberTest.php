<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the POS sale path: stock-derived order numbers, out-of-stock blocking,
 * and server-side pricing.
 */
class StockAndOrderNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Category $cylinders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();
    }

    private function cylinder(float $sizeKg, int $stock, float $price = 2000): Product
    {
        return Product::factory()
            ->cylinder($sizeKg)
            ->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function sell(array $items, array $overrides = [])
    {
        return $this->actingAs($this->cashier)->postJson('/pos/sales', array_merge([
            'cart_items' => $items,
            'payment_method' => 'cash',
        ], $overrides));
    }

    /** @test */
    public function order_number_is_the_stock_level_at_the_time_of_sale(): void
    {
        // The worked example from the specification: 6kg stock of 30.
        $sixKg = $this->cylinder(6, 30);

        $first = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $first->assertOk()->assertJson(['success' => true, 'order_number' => 30]);
        $this->assertSame(29, $sixKg->fresh()->stock);

        // Next sale must carry the new level.
        $second = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $second->assertOk()->assertJson(['order_number' => 29]);
        $this->assertSame(28, $sixKg->fresh()->stock);

        $third = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $third->assertOk()->assertJson(['order_number' => 28]);
        $this->assertSame(27, $sixKg->fresh()->stock);
    }

    /** @test */
    public function order_number_is_persisted_on_the_sale_and_on_each_line(): void
    {
        $sixKg = $this->cylinder(6, 30);

        $response = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $sale = Sale::latest('id')->first();

        $this->assertSame(30, $sale->order_number);
        $this->assertSame(30, $sale->items->first()->order_number);

        // receipt_number stays the unique audit identifier and is unaffected.
        $this->assertNotNull($sale->receipt_number);
        $this->assertNotSame((string) $sale->order_number, $sale->receipt_number);
        $response->assertJsonPath('receipt_data.order_number', 30);
    }

    /** @test */
    public function the_response_reports_resulting_stock_per_line(): void
    {
        // The terminal sets its on-screen stock straight from these figures.
        // It must never also subtract the quantity itself - doing both is what
        // made a sale of 1 appear to remove 2 from a stock of 50.
        $sixKg = $this->cylinder(6, 50);

        $response = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $response->assertOk()
            ->assertJsonPath('receipt_data.items.0.order_number', 50)
            ->assertJsonPath('receipt_data.items.0.stock_after', 49);

        $this->assertSame(49, $sixKg->fresh()->stock);
    }

    /** @test */
    public function quantity_greater_than_one_advances_the_number_by_that_amount(): void
    {
        $sixKg = $this->cylinder(6, 30);

        $this->sell([['id' => $sixKg->id, 'quantity' => 4]])
            ->assertOk()
            ->assertJson(['order_number' => 30]);

        $this->assertSame(26, $sixKg->fresh()->stock);

        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])
            ->assertOk()
            ->assertJson(['order_number' => 26]);
    }

    /** @test */
    public function each_cylinder_size_numbers_independently(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $thirteenKg = $this->cylinder(13, 12);

        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])
            ->assertJson(['order_number' => 30]);

        $this->sell([['id' => $thirteenKg->id, 'quantity' => 1]])
            ->assertJson(['order_number' => 12]);

        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])
            ->assertJson(['order_number' => 29]);

        $this->assertSame(28, $sixKg->fresh()->stock);
        $this->assertSame(11, $thirteenKg->fresh()->stock);
    }

    /** @test */
    public function mixed_cart_numbers_from_the_cylinder_line_and_records_every_line(): void
    {
        $accessories = Category::factory()->create(['name' => 'Cylinder Accessories']);
        $regulator = Product::factory()->withStock(120)->create([
            'category_id' => $accessories->id,
            'name' => 'Pressure Regulator',
        ]);
        $sixKg = $this->cylinder(6, 30);

        // Regulator listed first, but the cylinder should drive the headline.
        $this->sell([
            ['id' => $regulator->id, 'quantity' => 1],
            ['id' => $sixKg->id, 'quantity' => 1],
        ])->assertOk()->assertJson(['order_number' => 30]);

        $sale = Sale::latest('id')->first();

        $this->assertSame(30, $sale->order_number);
        $this->assertSame(
            120,
            $sale->items->firstWhere('product_id', $regulator->id)->order_number,
            'Every line carries its own stock number, cylinder or not.'
        );
        $this->assertSame(30, $sale->items->firstWhere('product_id', $sixKg->id)->order_number);
    }

    /*
    |--------------------------------------------------------------------------
    | Requirement 3: block sales when stock is depleted
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function sales_are_allowed_down_to_the_last_unit_then_blocked(): void
    {
        $sixKg = $this->cylinder(6, 2);

        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])->assertOk();   // 2 -> 1
        $this->assertSame(1, $sixKg->fresh()->stock);

        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])->assertOk();   // 1 -> 0
        $this->assertSame(0, $sixKg->fresh()->stock);

        // Stock 0 - must be refused.
        $blocked = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $blocked->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('out of stock', $blocked->json('message'));
        $this->assertSame(0, $sixKg->fresh()->stock, 'Stock must never go negative.');
    }

    /** @test */
    public function a_cart_asking_for_more_than_is_available_is_refused_entirely(): void
    {
        $sixKg = $this->cylinder(6, 3);

        $response = $this->sell([['id' => $sixKg->id, 'quantity' => 5]]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(3, $sixKg->fresh()->stock);
        $this->assertSame(0, Sale::count(), 'A refused sale must leave no sale row behind.');
    }

    /** @test */
    public function duplicate_lines_for_one_product_cannot_overdraw(): void
    {
        $sixKg = $this->cylinder(6, 3);

        // Two lines of 2 against a stock of 3: validated per-line this would pass.
        $response = $this->sell([
            ['id' => $sixKg->id, 'quantity' => 2],
            ['id' => $sixKg->id, 'quantity' => 2],
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(3, $sixKg->fresh()->stock);
    }

    /** @test */
    public function stock_reserved_for_a_cylinder_collection_is_not_sellable(): void
    {
        // Two units on the shelf, both promised to open drop-offs.
        $sixKg = $this->cylinder(6, 2);
        $sixKg->update(['reserved_stock' => 2]);

        $response = $this->sell([['id' => $sixKg->id, 'quantity' => 1]]);

        $response->assertStatus(422);
        $this->assertStringContainsString('reserved', $response->json('message'));
        $this->assertSame(2, $sixKg->fresh()->stock, 'Physical stock is untouched.');
    }

    /** @test */
    public function a_failed_sale_rolls_back_completely(): void
    {
        $ok = $this->cylinder(6, 10);
        $short = $this->cylinder(13, 1);

        $this->sell([
            ['id' => $ok->id, 'quantity' => 1],
            ['id' => $short->id, 'quantity' => 5],
        ])->assertStatus(422);

        $this->assertSame(10, $ok->fresh()->stock, 'The satisfiable line must not be deducted.');
        $this->assertSame(1, $short->fresh()->stock);
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleItem::count());
        $this->assertSame(0, StockMovement::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Pricing is server-side
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function prices_come_from_the_database_not_the_request(): void
    {
        $sixKg = $this->cylinder(6, 30, 2500.00);

        // A tampered client claiming the cylinder costs 1.
        $this->sell([['id' => $sixKg->id, 'quantity' => 2, 'price' => 1]])->assertOk();

        $sale = Sale::latest('id')->first();

        $this->assertEquals(5000.00, $sale->total_amount);
        $this->assertEquals(2500.00, $sale->items->first()->unit_price);
        $this->assertEquals(5000.00, $sale->items->first()->subtotal);
    }

    /*
    |--------------------------------------------------------------------------
    | Ledger and void
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function every_sale_writes_a_matching_stock_movement(): void
    {
        $sixKg = $this->cylinder(6, 30);

        $this->sell([['id' => $sixKg->id, 'quantity' => 3]])->assertOk();

        $movement = StockMovement::where('reference_type', 'sale')->first();

        $this->assertNotNull($movement);
        $this->assertSame('out', $movement->type);
        $this->assertSame(3, $movement->quantity);
        $this->assertSame($sixKg->id, $movement->product_id);
    }

    /** @test */
    public function voiding_a_sale_returns_the_stock(): void
    {
        $sixKg = $this->cylinder(6, 30);

        $this->sell([['id' => $sixKg->id, 'quantity' => 4]])->assertOk();
        $this->assertSame(26, $sixKg->fresh()->stock);

        $sale = Sale::latest('id')->first();

        $this->actingAs($this->cashier)
            ->post("/pos/sales/{$sale->id}/void")
            ->assertRedirect();

        $this->assertSame(Sale::STATUS_VOIDED, $sale->fresh()->status);
        $this->assertSame(30, $sixKg->fresh()->stock, 'Voiding must put the stock back.');
    }

    /** @test */
    public function a_sale_cannot_be_voided_twice(): void
    {
        $sixKg = $this->cylinder(6, 30);
        $this->sell([['id' => $sixKg->id, 'quantity' => 4]])->assertOk();

        $sale = Sale::latest('id')->first();

        $this->actingAs($this->cashier)->post("/pos/sales/{$sale->id}/void");
        $this->assertSame(30, $sixKg->fresh()->stock);

        // Second attempt must be a no-op, not a second restock.
        $this->actingAs($this->cashier)->post("/pos/sales/{$sale->id}/void");
        $this->assertSame(30, $sixKg->fresh()->stock);
    }

    /** @test */
    public function voided_sales_are_excluded_from_revenue_queries(): void
    {
        $sixKg = $this->cylinder(6, 30, 2500);

        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])->assertOk();
        $this->sell([['id' => $sixKg->id, 'quantity' => 1]])->assertOk();

        $toVoid = Sale::latest('id')->first();
        $this->actingAs($this->cashier)->post("/pos/sales/{$toVoid->id}/void");

        // The filter used throughout the reports.
        $revenue = Sale::where('status', '!=', 'voided')->sum('total_amount');

        $this->assertEquals(2500, $revenue);
        $this->assertEquals(2500, Sale::notVoided()->sum('total_amount'));
    }

    /*
    |--------------------------------------------------------------------------
    | Credit sales
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function a_credit_sale_raises_the_customer_balance_and_a_void_clears_it(): void
    {
        $sixKg = $this->cylinder(6, 30, 2500);
        $customer = Customer::factory()->create(['balance' => 0]);

        $this->sell(
            [['id' => $sixKg->id, 'quantity' => 2]],
            [
                'payment_method' => 'credit',
                'customer_details' => ['customer_id' => $customer->id],
            ]
        )->assertOk();

        $this->assertEquals(5000, $customer->fresh()->balance);

        $sale = Sale::latest('id')->first();
        $this->actingAs($this->cashier)->post("/pos/sales/{$sale->id}/void");

        $this->assertEquals(0, $customer->fresh()->balance);
    }
}
