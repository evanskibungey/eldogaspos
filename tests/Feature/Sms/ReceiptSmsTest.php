<?php

namespace Tests\Feature\Sms;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end cover for the receipt hooks: a real sale through the POS endpoint
 * must produce a message, and must not produce one when it should not.
 *
 * These guard the two failure modes that cost money or lose trust: texting
 * walk-in placeholders, and silently sending nothing at all.
 */
class ReceiptSmsTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Category $cylinders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();

        // The master switch seeds to off; these tests are about what happens
        // once an admin has turned it on.
        $this->setSetting('sms_enabled', '1');
    }

    /**
     * AppServiceProvider copies every setting into config('settings.*') at
     * boot, and setting() reads that snapshot before touching the database. A
     * test therefore has to write both, or it is asserting against the value
     * the app booted with rather than the one it just saved.
     */
    private function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        config(['settings.' . $key => $value]);
    }

    private function cylinder(int $stock = 20, float $price = 2000): Product
    {
        return Product::factory()
            ->cylinder(6)
            ->withStock($stock)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    public function test_a_credit_sale_to_a_real_customer_queues_a_receipt(): void
    {
        $product = $this->cylinder();
        $customer = Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 10000,
        ]);

        $response = $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'credit',
            'customer_details' => ['customer_id' => $customer->id],
        ]);

        $response->assertOk();

        $log = SmsLog::where('purpose', SmsLog::PURPOSE_SALE_RECEIPT)->first();

        $this->assertNotNull($log, 'A credit sale to a real customer should queue a receipt.');
        $this->assertSame('254712345678', $log->recipient);
        $this->assertSame($customer->id, $log->customer_id);
        $this->assertSame('sale', $log->reference_type);
    }

    /**
     * Cash sales are attributed to a shared walk-in customer whose number is a
     * placeholder. Texting it would bill a message on almost every sale.
     */
    public function test_a_cash_walk_in_sale_sends_nothing(): void
    {
        $product = $this->cylinder();

        $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertOk();

        $this->assertSame(0, SmsLog::count());
    }

    /**
     * The sale receipt: what was bought, what it cost, how it was paid. No
     * company line - the sender ID already says ELDOGAS, and those characters
     * buy the product name instead.
     */
    public function test_the_receipt_names_the_item_and_total(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $product = $this->cylinder(20, 1500);
        $customer = Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 10000,
        ]);

        $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'credit',
            'customer_details' => ['customer_id' => $customer->id],
        ])->assertOk();

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_SALE_RECEIPT)->first()->message;

        $this->assertStringContainsString('2x ' . $product->name, $message);
        $this->assertStringContainsString('3,000.00', $message);
        $this->assertStringContainsString('ItishaTunaDeliver, Asante.', $message);

        // The company name must not reappear as a header line.
        $this->assertStringStartsWith('2x ' . $product->name, $message);
    }

    private const FULL_STORE_LINK =
        'https://play.google.com/store/apps/details?id=co.ke.eldogas.customer&pcampaignid=web_share';

    private function sellTo(Customer $customer, string $paymentMethod): string
    {
        $product = $this->cylinder(20, 1500);

        $payload = [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => $paymentMethod,
            'customer_details' => ['customer_id' => $customer->id],
        ];

        $this->actingAs($this->cashier)->postJson('/pos/sales', $payload)->assertOk();

        return SmsLog::where('purpose', SmsLog::PURPOSE_SALE_RECEIPT)->latest('id')->first()->message;
    }

    private function namedCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 10000,
        ]);
    }

    /**
     * Dropping the company header bought enough room that even the long store
     * link fits a cash receipt in one billed message.
     */
    public function test_a_cash_receipt_fits_one_segment_even_with_the_full_store_link(): void
    {
        $this->setSetting('sms_app_link', self::FULL_STORE_LINK);

        $message = $this->sellTo($this->namedCustomer(), 'cash');

        $this->assertSame(
            1,
            \App\Services\Sms\SmsService::segments($message),
            'Cash receipt is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * A credit receipt carries an extra "Balance:" line, which is what tips it
     * over 160 when the long store link is used. Pinned so the cost is a stated
     * number: switching to the short /app link brings it back to one.
     */
    public function test_a_credit_receipt_costs_two_segments_on_the_full_store_link(): void
    {
        $this->setSetting('sms_app_link', self::FULL_STORE_LINK);

        $message = $this->sellTo($this->namedCustomer(), 'credit');

        $this->assertStringContainsString('Balance:', $message);
        $this->assertSame(2, \App\Services\Sms\SmsService::segments($message), $message);
    }

    public function test_the_short_link_keeps_both_receipt_types_to_one_segment(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $customer = $this->namedCustomer();

        foreach (['cash', 'credit'] as $method) {
            $message = $this->sellTo($customer, $method);

            $this->assertSame(
                1,
                \App\Services\Sms\SmsService::segments($message),
                ucfirst($method) . ' receipt is ' . mb_strlen($message) . " chars:\n" . $message
            );
        }
    }

    /**
     * The per-type toggle must be able to stop receipts without switching off
     * SMS for campaigns too.
     */
    public function test_turning_off_sale_receipts_stops_them(): void
    {
        $this->setSetting('sms_send_sale_receipts', '0');

        $product = $this->cylinder();
        $customer = Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 10000,
        ]);

        $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'credit',
            'customer_details' => ['customer_id' => $customer->id],
        ])->assertOk();

        $this->assertSame(0, SmsLog::count());
    }

    /**
     * The master switch has to win over the per-type toggles, otherwise
     * "SMS off" would not actually mean off.
     */
    public function test_the_master_switch_overrides_the_per_type_toggle(): void
    {
        $this->setSetting('sms_enabled', '0');
        config(['services.talksasa.driver' => 'talksasa', 'services.talksasa.token' => 'tok']);

        $product = $this->cylinder();
        $customer = Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 10000,
        ]);

        $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'credit',
            'customer_details' => ['customer_id' => $customer->id],
        ])->assertOk();

        $this->assertSame(0, SmsLog::count());
    }
}
