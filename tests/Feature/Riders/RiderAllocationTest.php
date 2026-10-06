<?php

namespace Tests\Feature\Riders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Rider;
use App\Models\RiderAllocation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SmsLog;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pick-up: cylinders leave the yard with a rider before any money is taken.
 *
 * The stock must be spoken for the moment they are booked out (or the same
 * cylinder gets sold at the till), but no sale may exist until the rider is
 * back - a cylinder on a bike can still come home unsold. The rider is texted
 * at both ends, and the second text must be sent exactly once, because every
 * message is billed.
 */
class RiderAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $admin;
    private Category $cylinders;
    private Rider $rider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->admin = User::factory()->admin()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();
        $this->rider = Rider::create([
            'name' => 'Peter Rider',
            'phone' => '0712345678',
            'status' => Rider::STATUS_ACTIVE,
        ]);
    }

    private function product(int $stock = 20, float $price = 3000): Product
    {
        return Product::factory()->cylinder(13)->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function pickUp(
        Product $product,
        int $quantity = 3,
        ?string $key = null,
        ?Rider $rider = null,
        ?string $customerPhone = null
    ) {
        $payload = [
            'rider_id' => ($rider ?? $this->rider)->id,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ];

        if ($key !== null) {
            $payload['idempotency_key'] = $key;
        }

        if ($customerPhone !== null) {
            $payload['customer_phone'] = $customerPhone;
        }

        return $this->actingAs($this->cashier)->postJson('/pos/riders/allocate', $payload);
    }

    private function complete(RiderAllocation $allocation)
    {
        return $this->actingAs($this->admin)
            ->postJson("/admin/riders/allocations/{$allocation->id}/complete");
    }

    /*
    |--------------------------------------------------------------------------
    | Booking out
    |--------------------------------------------------------------------------
    */

    public function test_a_pick_up_reserves_the_cylinders_and_texts_the_rider(): void
    {
        $product = $this->product(20);

        $response = $this->pickUp($product, 3)->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('rider.name', 'Peter Rider');

        $this->assertStringStartsWith('RDR', $response->json('reference_number'));

        $allocation = RiderAllocation::first();
        $this->assertSame(RiderAllocation::STATUS_PENDING, $allocation->status);
        $this->assertSame(RiderAllocation::STOCK_RESERVED, $allocation->stock_status);
        $this->assertEquals(9000, $allocation->total_amount);
        $this->assertNull($allocation->sale_id);

        $item = $allocation->items()->first();
        $this->assertSame(3, $item->quantity);
        $this->assertEquals(3000, $item->unit_price);

        // Spoken for, not gone: physical stock is unchanged, availability drops.
        $product->refresh();
        $this->assertSame(20, (int) $product->stock);
        $this->assertSame(3, (int) $product->reserved_stock);
        $this->assertSame(17, $product->available_stock);
        $response->assertJsonPath('items.0.stock_after', 17);

        // Nothing has been sold yet.
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, StockMovement::count());

        $sms = SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_ALLOCATED)->first();
        $this->assertNotNull($sms, 'The rider was not texted the pick-up.');
        $this->assertSame('254712345678', $sms->recipient);
        $this->assertStringContainsString($allocation->reference_number, $sms->message);
        $this->assertStringContainsString('3x 13kg Gas Cylinder', $sms->message);
    }

    /*
    |--------------------------------------------------------------------------
    | The optional customer number
    |--------------------------------------------------------------------------
    | It rides along in the rider's pick-up text so they can call ahead. It is
    | optional in the strict sense: omitting it, sending it empty and sending
    | it as whitespace must all behave exactly like an ordinary pick-up.
    */

    public function test_a_customer_number_reaches_the_rider_in_dialable_form(): void
    {
        $product = $this->product(20);

        $response = $this->pickUp($product, 1, null, null, '+254722884226')->assertOk();

        // Stored the way every other number in the system is stored...
        $allocation = RiderAllocation::first();
        $this->assertSame('254722884226', $allocation->customer_phone);

        // ...but written for a human to dial.
        $message = SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_ALLOCATED)->first()->message;
        $this->assertStringContainsString('Customer: 0722884226', $message);
        $this->assertStringNotContainsString('254722884226', $message);

        $response->assertJsonPath('customer_phone', '0722884226');
    }

    public function test_the_number_is_accepted_in_any_spelling(): void
    {
        foreach (['0722884226', '254722884226', '+254 722 884 226', '722884226'] as $i => $spelling) {
            $product = $this->product(20);
            $this->pickUp($product, 1, "key-{$i}", null, $spelling)->assertOk();

            $this->assertSame(
                '254722884226',
                RiderAllocation::latest('id')->first()->customer_phone,
                "Spelling '{$spelling}' was not normalised."
            );
        }
    }

    public function test_a_pick_up_without_a_customer_number_is_unchanged(): void
    {
        $product = $this->product(20);

        $this->pickUp($product, 1)->assertOk()->assertJsonPath('customer_phone', null);

        $this->assertNull(RiderAllocation::first()->customer_phone);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_ALLOCATED)->first()->message;
        $this->assertStringNotContainsString('Customer', $message);
    }

    public function test_an_empty_or_blank_number_is_treated_as_not_given(): void
    {
        foreach (['', '   '] as $i => $blank) {
            $product = $this->product(20);
            $this->pickUp($product, 1, "blank-{$i}", null, $blank)->assertOk();

            $this->assertNull(RiderAllocation::latest('id')->first()->customer_phone);
        }

        $this->assertSame(2, RiderAllocation::count());
    }

    public function test_an_unusable_number_is_refused_and_nothing_is_booked_out(): void
    {
        $product = $this->product(20);

        $this->pickUp($product, 3, null, null, '12345')
            ->assertStatus(422)
            ->assertJsonPath('error_type', 'invalid_customer_phone');

        // The whole pick-up is refused - no half-made allocation, no reserved
        // cylinders, and above all no SMS, because each one is billed.
        $this->assertSame(0, RiderAllocation::count());
        $this->assertSame(0, (int) $product->fresh()->reserved_stock);
        $this->assertSame(0, SmsLog::count());
    }

    public function test_the_customer_number_shows_on_the_management_page(): void
    {
        $product = $this->product(20);
        $this->pickUp($product, 1, null, null, '0722884226')->assertOk();

        $this->actingAs($this->admin)->get('/admin/riders')
            ->assertOk()
            ->assertSee('0722884226');
    }

    public function test_a_repeated_key_returns_the_allocation_already_made(): void
    {
        $product = $this->product(20);
        $key = 'pickup-attempt-1';

        $first = $this->pickUp($product, 3, $key)->assertOk();
        $second = $this->pickUp($product, 3, $key)->assertOk();

        $second->assertJsonPath('duplicate', true)
            ->assertJsonPath('reference_number', $first->json('reference_number'));

        $this->assertSame(1, RiderAllocation::count());
        $this->assertSame(3, (int) $product->fresh()->reserved_stock);
        $this->assertSame(1, SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_ALLOCATED)->count());
    }

    public function test_a_pick_up_is_refused_when_stock_is_short(): void
    {
        $product = $this->product(2);

        $this->pickUp($product, 3)
            ->assertStatus(422)
            ->assertJsonPath('error_type', 'insufficient_stock');

        $this->assertSame(0, RiderAllocation::count());
        $this->assertSame(0, (int) $product->fresh()->reserved_stock);
        $this->assertSame(0, SmsLog::count());
    }

    public function test_an_inactive_rider_cannot_be_assigned(): void
    {
        $this->rider->update(['status' => Rider::STATUS_INACTIVE]);

        $this->pickUp($this->product(), 1)->assertStatus(422);

        $this->assertSame(0, RiderAllocation::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Completion
    |--------------------------------------------------------------------------
    */

    public function test_completion_deducts_stock_records_the_sale_and_texts_the_rider(): void
    {
        $product = $this->product(20);
        $this->pickUp($product, 3)->assertOk();
        $allocation = RiderAllocation::first();

        $this->complete($allocation)->assertOk()->assertJsonPath('success', true);

        $allocation->refresh();
        $this->assertSame(RiderAllocation::STATUS_COMPLETED, $allocation->status);
        $this->assertSame(RiderAllocation::STOCK_COMMITTED, $allocation->stock_status);
        $this->assertNotNull($allocation->completed_at);
        $this->assertNotNull($allocation->completion_notified_at);

        // The reservation became a real deduction, once.
        $product->refresh();
        $this->assertSame(17, (int) $product->stock);
        $this->assertSame(0, (int) $product->reserved_stock);
        $this->assertSame(1, StockMovement::where('reference_type', 'rider_delivery')->count());

        // Now, and only now, there is a sale - and it is paid.
        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertSame($sale->id, $allocation->sale_id);
        $this->assertSame(Sale::STATUS_COMPLETED, $sale->status);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertEquals(9000, $sale->total_amount);
        $this->assertSame(3, SaleItem::where('sale_id', $sale->id)->first()->quantity);

        $sms = SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_COMPLETED)->first();
        $this->assertNotNull($sms, 'The rider was not told the order was completed.');
        $this->assertSame('254712345678', $sms->recipient);
        $this->assertSame(
            "Order #{$allocation->reference_number} has been completed successfully. Thank you.",
            $sms->message
        );
    }

    public function test_completing_twice_neither_deducts_nor_texts_again(): void
    {
        $product = $this->product(20);
        $this->pickUp($product, 3)->assertOk();
        $allocation = RiderAllocation::first();

        $this->complete($allocation)->assertOk();
        $this->complete($allocation)->assertStatus(422);

        $this->assertSame(17, (int) $product->fresh()->stock);
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_COMPLETED)->count());
    }

    public function test_cancelling_releases_the_cylinders_without_a_sale(): void
    {
        $product = $this->product(20);
        $this->pickUp($product, 3)->assertOk();
        $allocation = RiderAllocation::first();

        $this->actingAs($this->admin)
            ->postJson("/admin/riders/allocations/{$allocation->id}/cancel")
            ->assertOk();

        $allocation->refresh();
        $this->assertSame(RiderAllocation::STATUS_CANCELLED, $allocation->status);
        $this->assertSame(RiderAllocation::STOCK_RELEASED, $allocation->stock_status);

        $product->refresh();
        $this->assertSame(20, (int) $product->stock);
        $this->assertSame(0, (int) $product->reserved_stock);
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SmsLog::where('purpose', SmsLog::PURPOSE_RIDER_COMPLETED)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Availability and the management page
    |--------------------------------------------------------------------------
    */

    public function test_availability_follows_open_allocations(): void
    {
        $other = Rider::create(['name' => 'Jane Rider', 'phone' => '0722000000', 'status' => Rider::STATUS_ACTIVE]);
        $product = $this->product(20);

        $this->pickUp($product, 2)->assertOk();

        $riders = collect($this->actingAs($this->cashier)->getJson('/pos/riders/available')->assertOk()->json())
            ->keyBy('id');

        $this->assertTrue($riders[$this->rider->id]['is_out']);
        $this->assertSame('Out on delivery', $riders[$this->rider->id]['availability']);
        $this->assertFalse($riders[$other->id]['is_out']);
        $this->assertSame('Available', $riders[$other->id]['availability']);

        $this->complete(RiderAllocation::first())->assertOk();

        $riders = collect($this->actingAs($this->cashier)->getJson('/pos/riders/available')->json())->keyBy('id');
        $this->assertFalse($riders[$this->rider->id]['is_out']);
    }

    public function test_the_management_page_lists_what_is_out(): void
    {
        $product = $this->product(20);
        $this->pickUp($product, 2)->assertOk();
        $reference = RiderAllocation::first()->reference_number;

        $this->actingAs($this->admin)->get('/admin/riders')
            ->assertOk()
            ->assertSee($reference)
            ->assertSee('Peter Rider')
            ->assertSee('Out on delivery');

        $this->actingAs($this->cashier)->get('/pos/riders')
            ->assertOk()
            ->assertSee($reference);

        $this->actingAs($this->admin)->get("/admin/riders/{$this->rider->id}")
            ->assertOk()
            ->assertSee($reference)
            ->assertSee('2 cylinders held');
    }

    public function test_a_rider_registered_twice_by_phone_spelling_is_one_rider(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/riders', ['name' => 'Peter Again', 'phone' => '+254712345678'])
            ->assertRedirect();

        $this->assertSame(1, Rider::count());
    }
}
