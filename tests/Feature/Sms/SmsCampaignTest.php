<?php

namespace Tests\Feature\Sms;

use App\Jobs\SendSmsJob;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_an_admin_can_open_the_sms_history(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.sms.index'));

        $response->assertOk();
        $response->assertViewIs('admin.sms.index');
    }

    public function test_an_admin_can_open_the_compose_screen(): void
    {
        Customer::create(['name' => 'A', 'phone' => '0712345678', 'status' => 'active']);

        $response = $this->actingAs($this->admin())->get(route('admin.sms.compose'));

        $response->assertOk();
        $response->assertViewHas('audiences');
    }

    public function test_a_campaign_to_all_customers_is_queued(): void
    {
        Queue::fake();

        Customer::create(['name' => 'A', 'phone' => '0712345678', 'status' => 'active']);
        Customer::create(['name' => 'B', 'phone' => '0722345678', 'status' => 'active']);

        $response = $this->actingAs($this->admin())->post(route('admin.sms.send'), [
            'audience' => 'all',
            'message' => 'Gas offer this weekend!',
            'confirm' => '1',
        ]);

        $response->assertRedirect(route('admin.sms.index'));
        $response->assertSessionHas('success');

        $this->assertSame(2, SmsLog::campaigns()->count());
        Queue::assertPushed(SendSmsJob::class, 2);
    }

    /**
     * Sending without ticking the confirmation must fail: a campaign cannot be
     * recalled once it is queued.
     */
    public function test_a_campaign_without_confirmation_is_rejected(): void
    {
        Queue::fake();

        Customer::create(['name' => 'A', 'phone' => '0712345678', 'status' => 'active']);

        $response = $this->actingAs($this->admin())->post(route('admin.sms.send'), [
            'audience' => 'all',
            'message' => 'Gas offer this weekend!',
        ]);

        $response->assertSessionHasErrors('confirm');
        $this->assertSame(0, SmsLog::count());
        Queue::assertNothingPushed();
    }

    public function test_the_balance_audience_only_targets_customers_who_owe(): void
    {
        Queue::fake();

        Customer::create(['name' => 'Owes', 'phone' => '0712345678', 'status' => 'active', 'balance' => 500]);
        Customer::create(['name' => 'Clear', 'phone' => '0722345678', 'status' => 'active', 'balance' => 0]);

        $this->actingAs($this->admin())->post(route('admin.sms.send'), [
            'audience' => 'with_balance',
            'message' => 'You have a balance due.',
            'confirm' => '1',
        ]);

        $this->assertSame(1, SmsLog::campaigns()->count());
        $this->assertSame('254712345678', SmsLog::campaigns()->first()->recipient);
    }

    /**
     * The POS walk-in placeholder is a real customer row, so an "all customers"
     * campaign would otherwise try to text it.
     */
    public function test_the_walk_in_customer_is_never_targeted(): void
    {
        Queue::fake();

        Customer::create(['name' => 'Walk-in Customer', 'phone' => '0000000000', 'status' => 'active']);

        $response = $this->actingAs($this->admin())->post(route('admin.sms.send'), [
            'audience' => 'all',
            'message' => 'Promo',
            'confirm' => '1',
        ]);

        $response->assertSessionHasErrors('audience');
        $this->assertSame(0, SmsLog::count());
    }

    public function test_manual_numbers_are_normalised_and_invalid_ones_skipped(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())->post(route('admin.sms.send'), [
            'audience' => 'manual',
            'numbers' => "0712345678\n+254722345678\nnot-a-number\n0202345678",
            'message' => 'Promo',
            'confirm' => '1',
        ]);

        $recipients = SmsLog::campaigns()->pluck('recipient')->sort()->values()->all();

        $this->assertSame(['254712345678', '254722345678'], $recipients);
    }

    public function test_a_cashier_cannot_reach_the_sms_screens(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('admin.sms.index'))->assertForbidden();
    }
}
