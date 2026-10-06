<?php

namespace Tests\Feature\Sms;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\CampaignAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opting out of marketing SMS.
 *
 * The distinction this holds in place: a campaign is advertising and a receipt
 * is not. Someone who asks to stop hearing from us must be dropped from every
 * campaign, permanently - but must still get the receipt for a cylinder they
 * just bought, because that is about their own purchase.
 */
class SmsOptOutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->setSetting('sms_enabled', '1');
    }

    private function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        config(['settings.' . $key => $value]);
    }

    private function customer(string $phone, bool $optedOut = false): Customer
    {
        $customer = Customer::factory()->create(['phone' => $phone, 'status' => 'active']);

        if ($optedOut) {
            $customer->optOutOfSms('admin');
        }

        return $customer->fresh();
    }

    private function campaign(array $payload = [])
    {
        return $this->actingAs($this->admin)->post('/admin/sms/send', array_merge([
            'audience' => 'all',
            'period' => 'any',
            'message' => 'Order gas on the EldoGas app.',
            'confirm' => '1',
        ], $payload));
    }

    /*
    |--------------------------------------------------------------------------
    | Campaigns respect it
    |--------------------------------------------------------------------------
    */

    public function test_an_opted_out_customer_receives_no_campaign(): void
    {
        $this->customer('0712000001');
        $this->customer('0712000002', true);

        $this->campaign();

        $this->assertSame(1, SmsLog::where('purpose', SmsLog::PURPOSE_CAMPAIGN)->count());
        $this->assertSame('254712000001', SmsLog::first()->recipient);
    }

    public function test_the_preview_reports_who_was_excluded(): void
    {
        $this->customer('0712000001');
        $this->customer('0712000002', true);
        $this->customer('0712000003', true);

        $this->actingAs($this->admin)
            ->getJson('/admin/sms/audience-preview?' . http_build_query([
                'audience' => 'all',
                'period' => 'any',
                'message' => 'hello',
            ]))
            ->assertOk()
            ->assertJsonPath('recipients', 1)
            ->assertJsonPath('opted_out', 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Receipts are not marketing
    |--------------------------------------------------------------------------
    */

    public function test_an_opted_out_customer_still_gets_their_sale_receipt(): void
    {
        $this->setSetting('sms_send_sale_receipts', '1');

        $customer = $this->customer('0712000002', true);
        $category = Category::factory()->gasCylinders()->create();
        $product = Product::factory()->cylinder(6)->withStock(10)
            ->create(['category_id' => $category->id, 'price' => 1000]);

        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            // Naming a customer is what earns an SMS receipt; without this the
            // sale attaches to the anonymous walk-in placeholder.
            'customer_details' => ['customer_id' => $customer->id],
        ])->assertOk();

        $this->assertSame(
            1,
            SmsLog::where('purpose', SmsLog::PURPOSE_SALE_RECEIPT)->count(),
            'A receipt is about the customer\'s own purchase, not advertising.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | The footer
    |--------------------------------------------------------------------------
    */

    public function test_campaigns_carry_an_opt_out_line(): void
    {
        $this->customer('0712000001');

        $this->campaign(['message' => 'Free delivery this week.']);

        $this->assertStringContainsString('Reply STOP to opt out.', SmsLog::first()->message);
    }

    public function test_the_line_is_not_added_twice_when_already_written(): void
    {
        $this->customer('0712000001');

        $this->campaign(['message' => 'Free delivery. Text STOP to unsubscribe.']);

        $this->assertSame(
            1,
            substr_count(strtoupper(SmsLog::first()->message), 'STOP'),
            'An admin who wrote their own wording should not get it twice.'
        );
    }

    public function test_every_campaign_carries_the_app_link(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.ke/get');
        $this->customer('0712000001');

        // Written by hand, with no template and no Insert button pressed.
        $this->campaign(['message' => 'Free delivery this week.']);

        $sent = SmsLog::first()->message;
        $this->assertStringContainsString('https://eldogas.ke/get', $sent);
        $this->assertStringContainsString('Reply STOP to opt out.', $sent);
    }

    public function test_the_link_is_not_repeated_when_the_message_already_has_it(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.ke/get');
        $this->customer('0712000001');

        // What the "App promo" template produces - link already inline.
        $this->campaign(['message' => 'Order on the app. Download: https://eldogas.ke/get']);

        $this->assertSame(
            1,
            substr_count(SmsLog::first()->message, 'https://eldogas.ke/get'),
            'The templates put the link inline; it must not be appended again.'
        );
    }

    public function test_no_link_is_appended_when_none_is_configured(): void
    {
        $this->setSetting('sms_app_link', '');
        $this->customer('0712000001');

        $this->campaign(['message' => 'Free delivery this week.']);

        $this->assertStringContainsString('Reply STOP to opt out.', SmsLog::first()->message);
        $this->assertStringNotContainsString('http', SmsLog::first()->message);
    }

    public function test_the_estimate_counts_the_footer_it_adds(): void
    {
        $this->setSetting('sms_app_link', '');
        $this->customer('0712000001');

        // 150 chars fits one segment alone, but not once the footer is added.
        $message = str_repeat('a', 150);

        $this->actingAs($this->admin)
            ->getJson('/admin/sms/audience-preview?' . http_build_query([
                'audience' => 'all',
                'period' => 'any',
                'message' => $message,
            ]))
            ->assertOk()
            ->assertJsonPath('segments', 2)
            ->assertJsonPath('messages', 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Recording it
    |--------------------------------------------------------------------------
    */

    public function test_staff_can_opt_a_customer_out_and_back_in(): void
    {
        $customer = $this->customer('0712000001');

        $this->actingAs($this->admin)->post("/admin/sms/opt-out/{$customer->id}");
        $customer->refresh();
        $this->assertTrue($customer->sms_opt_out);
        $this->assertNotNull($customer->sms_opt_out_at);
        $this->assertSame('admin', $customer->sms_opt_out_source);

        $this->actingAs($this->admin)->post("/admin/sms/opt-out/{$customer->id}");
        $this->assertFalse($customer->fresh()->sms_opt_out);
    }

    public function test_opting_out_twice_keeps_the_date_they_first_asked(): void
    {
        $customer = $this->customer('0712000001');

        $customer->optOutOfSms('sms');
        $first = $customer->fresh()->sms_opt_out_at;

        $this->travel(5)->days();
        $customer->fresh()->optOutOfSms('admin');

        $this->assertSame(
            $first->toDateTimeString(),
            $customer->fresh()->sms_opt_out_at->toDateTimeString()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Inbound STOP
    |--------------------------------------------------------------------------
    */

    private function inbound(array $payload, ?string $secret = null)
    {
        return $this->postJson('/sms/inbound/' . ($secret ?? 'test-secret'), $payload);
    }

    public function test_texting_stop_opts_the_customer_out(): void
    {
        config(['services.talksasa.inbound_secret' => 'test-secret']);
        $customer = $this->customer('0712000001');

        $this->inbound(['from' => '254712000001', 'message' => 'STOP'])
            ->assertOk()
            ->assertJsonPath('action', 'opted_out');

        $customer->refresh();
        $this->assertTrue($customer->sms_opt_out);
        $this->assertSame('sms', $customer->sms_opt_out_source);
    }

    public function test_stop_reaches_every_spelling_of_the_same_number(): void
    {
        config(['services.talksasa.inbound_secret' => 'test-secret']);
        $a = $this->customer('0712345678');
        $b = $this->customer('+254712345678');

        $this->inbound(['from' => '254712345678', 'message' => 'stop'])->assertOk();

        $this->assertTrue($a->fresh()->sms_opt_out);
        $this->assertTrue($b->fresh()->sms_opt_out, 'One person, two records - both must stop.');
    }

    public function test_an_ordinary_reply_is_ignored(): void
    {
        config(['services.talksasa.inbound_secret' => 'test-secret']);
        $customer = $this->customer('0712000001');

        $this->inbound(['from' => '254712000001', 'message' => 'thank you'])
            ->assertOk()
            ->assertJsonPath('action', 'ignored');

        $this->assertFalse($customer->fresh()->sms_opt_out);
    }

    public function test_the_endpoint_is_closed_without_the_right_secret(): void
    {
        config(['services.talksasa.inbound_secret' => 'test-secret']);
        $customer = $this->customer('0712000001');

        $this->inbound(['from' => '254712000001', 'message' => 'STOP'], 'wrong-secret')
            ->assertNotFound();

        $this->assertFalse($customer->fresh()->sms_opt_out);
    }

    public function test_the_endpoint_is_closed_when_no_secret_is_configured(): void
    {
        config(['services.talksasa.inbound_secret' => null]);

        $this->inbound(['from' => '254712000001', 'message' => 'STOP'], 'anything')
            ->assertNotFound();
    }

    public function test_the_footer_wording_is_configurable(): void
    {
        $this->setSetting('sms_app_link', '');
        $this->setSetting('sms_opt_out_footer', 'Text STOP to end.');

        $this->assertSame(
            "Hello\nText STOP to end.",
            CampaignAudience::withFooter('Hello')
        );
    }

    /** Link first, then the way out - the offer should not read as an afterthought. */
    public function test_the_footer_is_the_link_then_the_opt_out(): void
    {
        $this->setSetting('sms_app_link', 'https://eldogas.ke/get');
        $this->setSetting('sms_opt_out_footer', 'Reply STOP to opt out.');

        $this->assertSame(
            "Free delivery this week.\nhttps://eldogas.ke/get\nReply STOP to opt out.",
            CampaignAudience::withFooter('Free delivery this week.')
        );
    }
}
