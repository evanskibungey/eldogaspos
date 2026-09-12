<?php

namespace Tests\Feature\Sms;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CylinderTransaction;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The messages a cylinder customer receives across the lifecycle: a receipt
 * when the transaction opens, a thank-you carrying the online-ordering offer
 * when it closes.
 *
 * Segment counts are asserted rather than assumed. The app link is 68
 * characters - most of a billed message - so a careless edit to the copy
 * doubles the cost of every thank-you the business sends.
 */
class CylinderSmsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const APP_LINK = 'https://play.google.com/store/apps/details?id=co.ke.eldogas.customer&pcampaignid=web_share';

    /**
     * The link without the share-sheet campaign tag. 22 characters shorter,
     * which is the difference between one and two segments on the thank-you.
     */
    private const APP_LINK_SHORT = 'https://play.google.com/store/apps/details?id=co.ke.eldogas.customer';

    private User $admin;
    private Category $cylinders;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();
        $this->customer = Customer::create([
            'name' => 'Evans Kibungei',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 100000,
        ]);

        $this->setSetting('sms_enabled', '1');
        $this->setSetting('sms_app_link', self::APP_LINK);
    }

    /**
     * Settings are snapshotted into config at boot, so a test has to write
     * both or it asserts against the value the app started with.
     */
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

    private function createDropOff(float $price = 2000): CylinderTransaction
    {
        $this->actingAs($this->admin)->postJson('/admin/cylinders', [
            'transaction_type' => 'drop_off',
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product($price)->id, 'quantity' => 1]],
            'payment_status' => 'pending',
        ])->assertOk();

        return CylinderTransaction::latest('id')->first();
    }

    private function complete(CylinderTransaction $transaction, array $payload = []): void
    {
        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/complete", $payload);
    }

    /*
    |--------------------------------------------------------------------------
    | Creation
    |--------------------------------------------------------------------------
    */

    public function test_creating_a_transaction_sends_a_receipt(): void
    {
        $transaction = $this->createDropOff();

        $log = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first();

        $this->assertNotNull($log);
        $this->assertSame('254712345678', $log->recipient);
        $this->assertStringContainsString('Drop-off', $log->message);
        $this->assertStringContainsString('to collect.', $log->message);
        $this->assertStringContainsString('ItishaTunaDeliver, Asante.', $log->message);

        // Every message now carries the app link.
        $this->assertStringContainsString(self::APP_LINK, $log->message);
    }

    /**
     * Customers recognise what they bought, not a reference number.
     */
    public function test_the_receipt_names_the_product_bought(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $transaction = $this->createDropOff();
        $product = $transaction->items->first()->product;

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringContainsString($product->name, $message);
    }

    /**
     * The product is named whatever the link costs. The drop-off receipt is the
     * tightest message in the system - items, collection reference, footer and
     * app link - and with the full store URL the fixed content alone exceeds
     * 160 characters. Naming the goods is the content, so the second part is
     * what gives way, not the name.
     */
    public function test_the_product_is_named_even_when_the_long_link_costs_a_second_segment(): void
    {
        $transaction = $this->createDropOff();
        $product = $transaction->items->first()->product;

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringContainsString($product->name, $message);
        $this->assertSame(
            2,
            SmsService::segments($message),
            'Drop-off receipt is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * The same message on the short link: product named AND one billed message.
     */
    /**
     * The drop-off receipt carries the app pitch as well as the items, the
     * collection reference and the footer, so it costs two billed parts on
     * either link - the short link no longer buys this message back down to
     * one. The product is still named, which is what the budget protects.
     */
    public function test_the_drop_off_receipt_names_the_product_and_costs_two_parts(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $transaction = $this->createDropOff();
        $product = $transaction->items->first()->product;

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringContainsString($product->name, $message);
        $this->assertStringContainsString('Did you know you can order using the EldoGas App', $message);
        $this->assertSame(
            2,
            SmsService::segments($message),
            'Drop-off receipt is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * Whatever the link, no message may reach a third billed part.
     */
    public function test_the_receipt_never_reaches_a_third_segment(): void
    {
        $transaction = $this->createDropOff();

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertLessThanOrEqual(2, SmsService::segments($message), $message);
    }

    /**
     * The company header is gone from the transactional receipts - the sender
     * ID already says ELDOGAS - and those characters went to the product name.
     */
    public function test_the_receipt_does_not_repeat_the_company_name(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $this->createDropOff();

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringStartsWith('Drop-off:', $message);
    }

    /**
     * The drop-off receipt is the exception that keeps its reference: it is the
     * token the customer quotes at the counter to collect.
     */
    public function test_an_active_drop_off_still_carries_its_collection_reference(): void
    {
        $transaction = $this->createDropOff();

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringContainsString($transaction->display_number, $message);
        $this->assertStringContainsString('to collect.', $message);
    }

    /**
     * The opening receipt is the longest of the transactional messages: a
     * drop-off that is unpaid, still active, and has no order number yet shows
     * every optional line at once, plus the app pitch. Even in that worst case
     * it must stay within two billed parts and still name the product.
     */
    public function test_the_opening_receipt_stays_within_two_parts_at_its_longest(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $transaction = $this->createDropOff();

        CylinderTransaction::whereKey($transaction->id)->update(['order_number' => null]);
        $transaction->refresh();

        $message = \App\Services\Sms\ReceiptMessage::cylinderCreated($transaction);

        // All the optional lines are present in this shape.
        $this->assertStringContainsString('Payment: PENDING', $message);
        $this->assertStringContainsString('to collect.', $message);
        $this->assertStringContainsString($transaction->reference_number, $message);

        // And there is still room to name what was dropped off.
        $this->assertStringContainsString(
            $transaction->items->first()->product->name,
            $message
        );

        $this->assertSame(
            2,
            SmsService::segments($message),
            'Opening receipt is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Completion
    |--------------------------------------------------------------------------
    */

    public function test_completing_a_transaction_sends_a_thank_you_with_the_offer(): void
    {
        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $log = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first();

        $this->assertNotNull($log, 'Completing should send a thank-you.');
        $this->assertStringContainsString('Thank you Evans!', $log->message);
        $this->assertStringContainsString('FREE home delivery', $log->message);
        $this->assertStringContainsString(self::APP_LINK, $log->message);
    }

    /**
     * A drop-off closes when the customer takes their refilled cylinders away.
     */
    public function test_completing_a_drop_off_says_collected(): void
    {
        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertStringContainsString('collected.', $message);
        $this->assertStringNotContainsString('complete.', $message);
    }

    /**
     * An advance collection closes the other way round - the customer brings
     * the empties back - so "collected" would describe the wrong movement.
     */
    public function test_completing_an_advance_collection_says_returned(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/cylinders', [
            'transaction_type' => 'advance_collection',
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
            'payment_status' => 'paid',
            'deposit_amount' => 500,
        ])->assertOk();

        $transaction = CylinderTransaction::latest('id')->first();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertStringContainsString('returned.', $message);
        $this->assertStringNotContainsString('collected.', $message);
    }

    /**
     * The greeting uses the first name only. A full name would be both odd to
     * read and long enough to tip the message into a second segment.
     */
    public function test_the_thank_you_greets_by_first_name_only(): void
    {
        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertStringContainsString('Evans', $message);
        $this->assertStringNotContainsString('Kibungei', $message);
    }

    /**
     * The thank-you goes to every completed transaction, so it is the message
     * whose segment count matters most.
     *
     * It currently bills as two even on a short link: the promotional copy is
     * 108 characters, which leaves no room. Pinned so the cost is a stated
     * number rather than a surprise, and so any trim that gets it back to one
     * is noticed.
     */
    public function test_the_thank_you_cost_is_pinned(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertSame(
            2,
            SmsService::segments($message),
            'Thank-you is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * Whatever the link and copy, the thank-you must never reach a third.
     */
    public function test_the_thank_you_never_reaches_a_third_segment(): void
    {
        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertStringContainsString('pcampaignid=web_share', $message);
        $this->assertLessThanOrEqual(2, SmsService::segments($message), $message);
    }

    /**
     * Whatever the link, the thank-you must never reach a third segment.
     */
    public function test_a_long_customer_name_never_pushes_past_two_segments(): void
    {
        $this->customer->update(['name' => 'Bartholomew Wanyonyi-Chesire']);

        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertLessThanOrEqual(2, SmsService::segments($message), $message);
    }

    public function test_completing_does_not_resend_the_opening_receipt(): void
    {
        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $this->assertSame(
            1,
            SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->count(),
            'The opening receipt should be sent once, at creation.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Toggles
    |--------------------------------------------------------------------------
    */

    /**
     * The promotional message must be switchable without losing the
     * transactional one.
     */
    public function test_the_thank_you_can_be_switched_off_independently(): void
    {
        $this->setSetting('sms_send_thank_you', '0');

        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $this->assertSame(1, SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->count());
        $this->assertSame(0, SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->count());
    }

    /**
     * With no link configured the customer still gets thanked, just without the
     * advert - the message must not end with a dangling "delivery:".
     */
    public function test_clearing_the_app_link_sends_thanks_without_the_offer(): void
    {
        $this->setSetting('sms_app_link', '');

        $transaction = $this->createDropOff();
        $this->complete($transaction);

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_THANK_YOU)->first()->message;

        $this->assertStringContainsString('Thank you Evans!', $message);
        $this->assertStringNotContainsString('FREE home delivery', $message);
        $this->assertStringNotContainsString('http', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Later payment
    |--------------------------------------------------------------------------
    */

    public function test_recording_a_later_payment_confirms_it_rather_than_resending_a_receipt(): void
    {
        $transaction = $this->createDropOff(3500);
        $this->complete($transaction, ['payment_status' => 'pending']);

        $this->actingAs($this->admin)->post("/admin/cylinders/{$transaction->id}/record-payment");

        $log = SmsLog::where('purpose', SmsLog::PURPOSE_PAYMENT_RECEIVED)->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Payment received', $log->message);
        $this->assertStringContainsString('3,500.00', $log->message);
        $this->assertStringContainsString('Thank you for refilling your gas with us.', $log->message);
        $this->assertStringContainsString('FREE home delivery and earn points', $log->message);
        $this->assertStringContainsString(self::APP_LINK, $log->message);

        // Receipt + offer + link cannot fit 160 characters; two is the accepted
        // cost of carrying the link here.
        $this->assertSame(2, SmsService::segments($log->message), $log->message);
    }

    /**
     * display_number falls back to the reference number when a transaction was
     * never assigned an order number, which is twelve characters longer. That
     * must not tip the message into a third segment.
     */
    /**
     * The payment confirmation now names what was paid for rather than quoting
     * a reference the customer never saw.
     */
    public function test_the_payment_message_names_the_item_not_the_reference(): void
    {
        $transaction = $this->createDropOff(3500);
        $product = $transaction->items->first()->product;

        $message = \App\Services\Sms\ReceiptMessage::paymentReceived($transaction);

        $this->assertStringContainsString($product->name, $message);
        $this->assertStringNotContainsString($transaction->reference_number, $message);

        $this->assertSame(
            2,
            SmsService::segments($message),
            'Payment message is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * A six-figure total is the widest the amount ever gets. Combined with the
     * long reference it is the worst case for this message.
     */
    public function test_the_payment_message_never_reaches_a_third_segment(): void
    {
        $transaction = $this->createDropOff(3500);

        CylinderTransaction::whereKey($transaction->id)->update([
            'order_number' => null,
            'amount' => 250000,
            'deposit_amount' => 0,
        ]);
        $transaction->refresh();

        $message = \App\Services\Sms\ReceiptMessage::paymentReceived($transaction);

        $this->assertStringContainsString('250,000.00', $message);
        $this->assertSame(
            2,
            SmsService::segments($message),
            'Payment message is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Short link
    |--------------------------------------------------------------------------
    */

    /**
     * Customers open this from a text message, so it must work with no session.
     */
    public function test_the_short_app_link_redirects_to_the_store_unauthenticated(): void
    {
        $this->setSetting('app_store_url', self::APP_LINK);

        $this->get('/app')
            ->assertRedirect(self::APP_LINK);
    }

    public function test_the_short_link_404s_when_no_destination_is_configured(): void
    {
        $this->setSetting('app_store_url', '');

        $this->get('/app')->assertNotFound();
    }

    /**
     * Both settings are typed by hand and it is easy to paste the short link
     * into the destination field as well. That made /app redirect to itself,
     * which customers saw as ERR_TOO_MANY_REDIRECTS when they tapped the link
     * in their SMS.
     */
    public function test_the_short_link_refuses_to_redirect_to_itself(): void
    {
        $this->setSetting('app_store_url', url('/app'));

        $this->get('/app')->assertNotFound();
    }

    public function test_a_trailing_slash_does_not_defeat_the_loop_guard(): void
    {
        $this->setSetting('app_store_url', url('/app') . '/');

        $this->get('/app')->assertNotFound();
    }

    /**
     * The short link is used verbatim in the message. It no longer brings this
     * particular message down to one billed part - the app pitch spends what
     * the shorter link saved - but it still buys the room that keeps the
     * product named, and it keeps the POS sale receipt at one part.
     */
    public function test_the_short_link_is_used_verbatim_in_the_receipt(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.co.ke/app');

        $transaction = $this->createDropOff();
        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringContainsString('https://eldogas.co.ke/app', $message);
        $this->assertSame(
            2,
            SmsService::segments($message),
            'Opening receipt is ' . mb_strlen($message) . " chars:\n" . $message
        );
    }

    /**
     * With the full Play Store URL the message is far longer still, but the
     * ceiling holds: two parts, never three.
     */
    public function test_the_full_store_url_still_stays_within_two_parts(): void
    {
        $transaction = $this->createDropOff();

        $message = SmsLog::where('purpose', SmsLog::PURPOSE_CYLINDER_RECEIPT)->first()->message;

        $this->assertStringContainsString('pcampaignid=web_share', $message);
        $this->assertSame(2, SmsService::segments($message), $message);
    }

    /**
     * Clearing the link strips it from the payment message too, leaving the
     * receipt and the offer without a dangling blank line.
     */
    public function test_clearing_the_app_link_also_strips_it_from_the_payment_message(): void
    {
        $this->setSetting('sms_app_link', '');

        $transaction = $this->createDropOff(3500);
        $message = \App\Services\Sms\ReceiptMessage::paymentReceived($transaction);

        $this->assertStringContainsString('Payment received', $message);
        $this->assertStringNotContainsString('http', $message);
        $this->assertSame('.', mb_substr($message, -1));
    }
}
