<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Sms\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quick sale: one tap on a product tile is the whole transaction. There is no
 * cart, no review step and no confirm dialog.
 *
 * That removes every client-side protection against selling the same thing
 * twice, so the guarantee has to live on the server: a repeated idempotency key
 * returns the sale already made rather than making another. These tests are
 * mostly about that.
 */
class QuickSaleTest extends TestCase
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

    private function product(int $stock = 20, float $price = 2000): Product
    {
        return Product::factory()->cylinder(6)->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function tap(Product $product, int $quantity = 1, ?string $key = null)
    {
        $payload = [
            'cart_items' => [['id' => $product->id, 'quantity' => $quantity]],
            'payment_method' => 'cash',
        ];

        if ($key !== null) {
            $payload['idempotency_key'] = $key;
        }

        return $this->actingAs($this->cashier)->postJson('/pos/sales', $payload);
    }

    /*
    |--------------------------------------------------------------------------
    | One tap sells
    |--------------------------------------------------------------------------
    */

    public function test_a_single_tap_completes_a_sale(): void
    {
        $product = $this->product(20);

        $response = $this->tap($product)->assertOk();

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertEquals(2000, $sale->total_amount);

        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('receipt_number'));
    }

    public function test_the_sale_is_recorded_against_the_logged_in_user(): void
    {
        $this->tap($this->product())->assertOk();

        $this->assertSame($this->cashier->id, Sale::first()->user_id);
    }

    public function test_stock_is_deducted_and_a_movement_written(): void
    {
        $product = $this->product(20);

        $this->tap($product)->assertOk();

        $this->assertSame(19, $product->fresh()->stock);
        $this->assertSame(
            1,
            StockMovement::where('reference_type', 'sale')->where('product_id', $product->id)->count()
        );
    }

    public function test_a_tap_with_no_customer_falls_back_to_walk_in(): void
    {
        $this->tap($this->product())->assertOk();

        $this->assertSame(PhoneNumber::WALK_IN, Sale::first()->customer->phone);
    }

    /**
     * The quantity stepper sends a count; it must be one sale, not several.
     */
    public function test_the_quantity_stepper_sells_several_units_in_one_sale(): void
    {
        $product = $this->product(20, 1500);

        $this->tap($product, 3)->assertOk();

        $this->assertSame(1, Sale::count());
        $this->assertSame(1, SaleItem::count());
        $this->assertSame(3, SaleItem::first()->quantity);
        $this->assertEquals(4500, Sale::first()->total_amount);
        $this->assertSame(17, $product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | It cannot sell twice
    |--------------------------------------------------------------------------
    */

    /**
     * The double-click case. Two requests carrying the same key are one sale.
     */
    public function test_the_same_idempotency_key_never_makes_a_second_sale(): void
    {
        $product = $this->product(20);
        $key = 'tap-0001';

        $first = $this->tap($product, 1, $key)->assertOk();
        $second = $this->tap($product, 1, $key)->assertOk();

        $this->assertSame(1, Sale::count(), 'A repeated key must not create a second sale.');
        $this->assertSame(19, $product->fresh()->stock, 'Stock must be deducted once.');

        // The till still gets a usable receipt back, so it can print.
        $this->assertSame($first->json('receipt_number'), $second->json('receipt_number'));
        $this->assertSame($first->json('sale_id'), $second->json('sale_id'));
        $this->assertTrue($second->json('duplicate'));
    }

    public function test_the_replayed_response_can_still_print_a_receipt(): void
    {
        $product = $this->product(20, 1500);
        $key = 'tap-0002';

        $this->tap($product, 2, $key)->assertOk();
        $replay = $this->tap($product, 2, $key)->assertOk();

        $data = $replay->json('receipt_data');

        $this->assertSame(2, $data['items'][0]['quantity']);
        $this->assertSame($product->name, $data['items'][0]['name']);
        $this->assertEquals(3000, $data['total']);
        $this->assertNotEmpty($data['date']);
    }

    /**
     * Different taps are different sales - the key must not collapse genuine
     * repeat business into one transaction.
     */
    public function test_different_keys_make_different_sales(): void
    {
        $product = $this->product(20);

        $this->tap($product, 1, 'tap-a')->assertOk();
        $this->tap($product, 1, 'tap-b')->assertOk();

        $this->assertSame(2, Sale::count());
        $this->assertSame(18, $product->fresh()->stock);
    }

    /**
     * Older clients and the API send no key at all; they must keep working.
     */
    public function test_a_sale_without_a_key_still_works(): void
    {
        $product = $this->product(20);

        $this->tap($product)->assertOk();
        $this->tap($product)->assertOk();

        $this->assertSame(2, Sale::count());
        $this->assertNull(Sale::first()->idempotency_key);
    }

    public function test_a_tap_beyond_available_stock_is_refused(): void
    {
        $product = $this->product(2);

        $this->tap($product, 5)->assertStatus(422);

        $this->assertSame(0, Sale::count());
        $this->assertSame(2, $product->fresh()->stock, 'A refused sale must not move stock.');
    }

    /*
    |--------------------------------------------------------------------------
    | The screen
    |--------------------------------------------------------------------------
    */

    public function test_the_pos_screen_sells_on_click_and_prints_from_a_button(): void
    {
        $this->product();

        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('@click="sellOnClick(product)"', $html);
        $this->assertStringContainsString('adjustQty(product, 1)', $html);
        $this->assertStringContainsString('idempotency_key', $html);

        // Printing is an explicit button on the tile, beside Pick-up.
        $this->assertStringContainsString('@click="sellAndPrint(product)"', $html);
    }

    /**
     * Double-click used to be how a receipt got printed. It was unreliable -
     * it selected the product name instead of firing - so it was replaced by
     * the Print button. The binding must be gone, or a cashier double-tapping
     * out of habit gets a second, silent sale.
     */
    public function test_the_tile_no_longer_reacts_to_a_double_click(): void
    {
        $this->product();

        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('printOnDoubleClick', $html);
        $this->assertStringNotContainsString('@dblclick', $html);
    }

    public function test_the_cart_is_gone_from_the_pos_screen(): void
    {
        $this->product();

        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('addToCart(', $html);
        $this->assertStringNotContainsString('Complete Sale', $html);
        $this->assertStringNotContainsString('this.cart', $html);
    }
}
