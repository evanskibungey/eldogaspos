<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A cash sale may now name a customer, the same way a credit sale does.
 *
 * Cash used to be filed against a shared walk-in placeholder with no way to
 * record who bought - which also meant no SMS receipt, since the placeholder's
 * number is not sendable. Naming the customer stays optional: a queue should
 * not be held up by it.
 */
class CashSaleCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Category $cylinders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();

        $this->setSetting('sms_enabled', '1');
    }

    private function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        config(['settings.' . $key => $value]);
    }

    private function product(float $price = 2000): Product
    {
        return Product::factory()->cylinder(6)->withStock(20)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function sell(array $overrides = [])
    {
        return $this->actingAs($this->cashier)->postJson('/pos/sales', array_merge([
            'cart_items' => [['id' => $this->product()->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | Cash without a customer
    |--------------------------------------------------------------------------
    */

    public function test_a_cash_sale_still_works_with_no_customer(): void
    {
        $this->sell()->assertOk();

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertSame(PhoneNumber::WALK_IN, $sale->customer->phone);
    }

    /**
     * An explicit null - what the POS sends when the cashier chose walk-in -
     * must be treated as "no customer", not as invalid input.
     */
    public function test_an_explicit_null_customer_is_treated_as_walk_in(): void
    {
        $this->sell(['customer_details' => null])->assertOk();

        $this->assertSame(PhoneNumber::WALK_IN, Sale::first()->customer->phone);
    }

    public function test_a_walk_in_cash_sale_sends_no_sms(): void
    {
        $this->sell()->assertOk();

        $this->assertSame(0, SmsLog::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Cash with a customer
    |--------------------------------------------------------------------------
    */

    public function test_a_cash_sale_can_name_a_new_customer(): void
    {
        $this->sell([
            'customer_details' => ['name' => 'Jane Wanjiku', 'phone' => '0712345678'],
        ])->assertOk();

        $customer = Customer::where('phone', '0712345678')->first();

        $this->assertNotNull($customer, 'The cash sale should create the named customer.');
        $this->assertSame('Jane Wanjiku', $customer->name);
        $this->assertSame($customer->id, Sale::first()->customer_id);
    }

    public function test_a_cash_sale_can_select_an_existing_customer(): void
    {
        $existing = Customer::create([
            'name' => 'Jane Wanjiku',
            'phone' => '0712345678',
            'status' => 'active',
        ]);

        $this->sell([
            'customer_details' => ['customer_id' => $existing->id],
        ])->assertOk();

        $this->assertSame($existing->id, Sale::first()->customer_id);
        $this->assertSame(1, Customer::where('phone', '0712345678')->count());
    }

    /**
     * The point of naming a cash customer: they get their receipt by SMS.
     */
    public function test_a_named_cash_customer_receives_an_sms_receipt(): void
    {
        $this->sell([
            'customer_details' => ['name' => 'Jane Wanjiku', 'phone' => '0712345678'],
        ])->assertOk();

        $log = SmsLog::where('purpose', SmsLog::PURPOSE_SALE_RECEIPT)->first();

        $this->assertNotNull($log, 'A named cash customer should get a receipt.');
        $this->assertSame('254712345678', $log->recipient);
    }

    /**
     * A cash sale must not put the amount on the customer's balance - only
     * credit does that.
     */
    public function test_a_named_cash_sale_does_not_touch_the_balance(): void
    {
        $existing = Customer::create([
            'name' => 'Jane Wanjiku',
            'phone' => '0712345678',
            'status' => 'active',
        ]);

        $this->sell([
            'customer_details' => ['customer_id' => $existing->id],
        ])->assertOk();

        $this->assertEquals(0, $existing->fresh()->balance);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Optional does not mean half-filled: a name with no number identifies
     * nobody and cannot be texted.
     */
    public function test_a_cash_customer_without_a_phone_is_rejected(): void
    {
        $this->sell([
            'customer_details' => ['name' => 'Jane Wanjiku'],
        ])->assertStatus(422);

        $this->assertSame(0, Sale::count());
    }

    public function test_credit_still_requires_a_customer(): void
    {
        $this->sell(['payment_method' => 'credit'])->assertStatus(422);

        $this->assertSame(0, Sale::count());
    }

    /*
    |--------------------------------------------------------------------------
    | The screen
    |--------------------------------------------------------------------------
    */

    /**
     * The POS screen no longer offers a customer picker - quick-sale sells
     * against the walk-in record. The endpoint still accepts customer details,
     * which is what every other test in this file covers, so the capability is
     * intact for the API and for any screen that wants it back.
     */
    public function test_the_pos_screen_no_longer_picks_a_customer(): void
    {
        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString("customerMode", $html);
        $this->assertStringNotContainsString('paymentMethod', $html);
    }
}
