<?php

namespace Tests\Feature\Sms;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\CampaignAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Targeting a campaign by when customers joined.
 *
 * Every recipient is billed per segment, so the number an admin approves in
 * the preview has to be the number that actually goes out. These tests mostly
 * exist to hold those two together: the preview and the send resolve their
 * audience through the same service, and both drop numbers that cannot be
 * dialled and people held twice under different spellings.
 */
class CampaignTargetingTest extends TestCase
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

    private function customerJoined(string $when, string $phone): Customer
    {
        $customer = Customer::factory()->create(['phone' => $phone, 'status' => 'active']);
        $customer->forceFill(['created_at' => \Carbon\Carbon::parse($when)])->save();

        return $customer->fresh();
    }

    private function send(array $payload)
    {
        return $this->actingAs($this->admin)->post('/admin/sms/send', array_merge([
            'audience' => 'all',
            'message' => 'Order gas on the EldoGas app.',
            'confirm' => '1',
        ], $payload));
    }

    private function preview(array $params)
    {
        return $this->actingAs($this->admin)
            ->getJson('/admin/sms/audience-preview?' . http_build_query($params));
    }

    /*
    |--------------------------------------------------------------------------
    | The windows
    |--------------------------------------------------------------------------
    */

    public function test_this_month_and_last_month_are_calendar_months(): void
    {
        // An admin asking for "last month" means the month on the wall, not
        // the last 30 days.
        $this->customerJoined(now()->startOfMonth()->addDay()->toDateString(), '0712000001');
        $this->customerJoined(now()->subMonthNoOverflow()->startOfMonth()->addDay()->toDateString(), '0712000002');
        $this->customerJoined(now()->subMonthsNoOverflow(2)->startOfMonth()->addDay()->toDateString(), '0712000003');

        $audience = app(CampaignAudience::class);

        $this->assertSame(1, $audience->query('all', CampaignAudience::PERIOD_THIS_MONTH)->count());
        $this->assertSame(1, $audience->query('all', CampaignAudience::PERIOD_LAST_MONTH)->count());
    }

    public function test_the_rolling_windows_reach_further_back(): void
    {
        $this->customerJoined(now()->subMonthsNoOverflow(2)->toDateString(), '0712000001');
        $this->customerJoined(now()->subMonthsNoOverflow(3)->addDays(5)->toDateString(), '0712000002');
        $this->customerJoined(now()->subMonthsNoOverflow(5)->toDateString(), '0712000003');

        $audience = app(CampaignAudience::class);

        $this->assertSame(2, $audience->query('all', CampaignAudience::PERIOD_LAST_3_MONTHS)->count());
        $this->assertSame(2, $audience->query('all', CampaignAudience::PERIOD_LAST_4_MONTHS)->count());
        $this->assertSame(3, $audience->query('all', CampaignAudience::PERIOD_ANY)->count());
    }

    public function test_a_custom_range_includes_both_end_days(): void
    {
        $this->customerJoined('2026-03-01 08:00:00', '0712000001');
        $this->customerJoined('2026-03-31 23:30:00', '0712000002');
        $this->customerJoined('2026-04-01 00:30:00', '0712000003');

        $audience = app(CampaignAudience::class);

        $this->assertSame(
            2,
            $audience->query('all', CampaignAudience::PERIOD_CUSTOM, '2026-03-01', '2026-03-31')->count(),
            'Someone who joined at 23:30 on the last day is inside the range.'
        );
    }

    public function test_reversed_custom_dates_still_match_the_range(): void
    {
        $this->customerJoined('2026-03-15', '0712000001');

        $audience = app(CampaignAudience::class);

        $this->assertSame(
            1,
            $audience->query('all', CampaignAudience::PERIOD_CUSTOM, '2026-03-31', '2026-03-01')->count(),
            'Dates entered the wrong way round should not silently match nobody.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Who is actually reachable
    |--------------------------------------------------------------------------
    */

    public function test_the_same_person_under_two_spellings_is_messaged_once(): void
    {
        $this->customerJoined(now()->toDateString(), '0712345678');
        $this->customerJoined(now()->toDateString(), '+254712345678');

        $this->send(['period' => 'this_month']);

        $this->assertSame(1, SmsLog::where('purpose', SmsLog::PURPOSE_CAMPAIGN)->count());
        $this->assertSame('254712345678', SmsLog::first()->recipient);
    }

    public function test_unusable_numbers_are_dropped_not_billed(): void
    {
        $this->customerJoined(now()->toDateString(), '0712000001');
        $this->customerJoined(now()->toDateString(), '12345');

        $this->send(['period' => 'this_month']);

        $this->assertSame(1, SmsLog::where('purpose', SmsLog::PURPOSE_CAMPAIGN)->count());
    }

    public function test_the_walk_in_placeholder_never_receives_a_campaign(): void
    {
        Customer::factory()->create(['phone' => '0000000000', 'name' => 'Walk-in Customer', 'status' => 'active']);
        $this->customerJoined(now()->toDateString(), '0712000001');

        $this->send(['period' => 'this_month']);

        $this->assertSame(1, SmsLog::count());
        $this->assertSame('254712000001', SmsLog::first()->recipient);
    }

    /*
    |--------------------------------------------------------------------------
    | The preview must equal the send
    |--------------------------------------------------------------------------
    */

    public function test_the_preview_reports_what_the_send_will_bill(): void
    {
        $this->customerJoined(now()->toDateString(), '0712345678');
        $this->customerJoined(now()->toDateString(), '254712345678');   // same person
        $this->customerJoined(now()->toDateString(), 'not-a-number');   // undialable
        $this->customerJoined(now()->subYear()->toDateString(), '0712000009'); // outside the window

        // Pinned so the segment arithmetic is about the message, not about
        // whatever length the app link happens to be set to.
        $this->setSetting('sms_app_link', '');

        // 200 chars, plus the appended opt-out line, is still two segments.
        $message = str_repeat('a', 200);

        $preview = $this->preview([
            'audience' => 'all',
            'period' => 'this_month',
            'message' => $message,
        ])->assertOk();

        $preview->assertJsonPath('matched', 3)
            ->assertJsonPath('recipients', 1)
            ->assertJsonPath('skipped', 2)
            ->assertJsonPath('segments', 2)
            ->assertJsonPath('messages', 2);

        // And the send agrees.
        $this->send(['period' => 'this_month', 'message' => $message]);

        $this->assertSame(
            $preview->json('recipients'),
            SmsLog::where('purpose', SmsLog::PURPOSE_CAMPAIGN)->count(),
            'The preview promised a different number than was queued.'
        );
    }

    public function test_the_preview_sends_nothing(): void
    {
        $this->customerJoined(now()->toDateString(), '0712000001');

        $this->preview(['audience' => 'all', 'period' => 'any', 'message' => 'hello'])->assertOk();

        $this->assertSame(0, SmsLog::count(), 'A preview must never queue a message.');
    }

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    */

    public function test_a_custom_range_without_dates_is_refused(): void
    {
        $this->customerJoined(now()->toDateString(), '0712000001');

        $this->send(['period' => 'custom'])
            ->assertSessionHasErrors(['date_from', 'date_to']);

        $this->assertSame(0, SmsLog::count());
    }

    public function test_a_window_matching_nobody_queues_nothing_and_says_so(): void
    {
        $this->customerJoined(now()->subYears(2)->toDateString(), '0712000001');

        $this->send(['period' => 'this_month'])->assertSessionHasErrors('audience');

        $this->assertSame(0, SmsLog::count());
    }

    public function test_the_compose_screen_shows_the_customer_base(): void
    {
        $this->customerJoined(now()->toDateString(), '0712000001');
        $this->customerJoined(now()->subMonthNoOverflow()->toDateString(), '0712000002');

        $this->actingAs($this->admin)->get('/admin/sms/compose')
            ->assertOk()
            ->assertSee('When did they join?')
            ->assertSee('this month')
            ->assertSee('in the last 3 months')
            ->assertSee('Custom range');
    }

    /**
     * Every registration window is selectable as a radio, not just described.
     * The cards ARE the control - a card you cannot choose is decoration.
     */
    public function test_every_period_is_selectable_on_the_compose_screen(): void
    {
        $this->customerJoined(now()->toDateString(), '0712000001');

        $html = $this->actingAs($this->admin)->get('/admin/sms/compose')->assertOk()->getContent();

        foreach (array_keys(CampaignAudience::periods()) as $period) {
            $this->assertStringContainsString(
                'name="period" value="' . $period . '"',
                $html,
                "The '{$period}' window has no radio to select it."
            );
        }
    }

    public function test_the_compose_screen_links_both_sms_pages_from_the_sidebar(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/sms/compose')->assertOk()->getContent();

        // Compose had no way in but the URL until it was added to the menu.
        $this->assertStringContainsString(route('admin.sms.compose'), $html);
        $this->assertStringContainsString(route('admin.sms.index'), $html);
        $this->assertStringContainsString('Compose Campaign', $html);
        $this->assertStringContainsString('Message History', $html);
    }
}
