<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Sales" badge in the POS header.
 *
 * It must agree with the admin Sales Overview, which counts today's sales
 * excluding voided ones. Two screens showing different numbers for the same
 * day is worse than showing no number at all - a cashier cashing up cannot
 * tell which to believe.
 */
class TodaySalesBadgeTest extends TestCase
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

    private function product(float $price = 1000): Product
    {
        return Product::factory()->cylinder(6)->withStock(50)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function sell(Product $product, ?string $key = null)
    {
        $payload = [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ];

        if ($key !== null) {
            $payload['idempotency_key'] = $key;
        }

        return $this->actingAs($this->cashier)->postJson('/pos/sales', $payload);
    }

    /** The count and amount Alpine is seeded with at page load. */
    private function seeded(): array
    {
        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        preg_match('/todaySalesCount:\s*(\d+)/', $html, $c);
        preg_match('/todaySalesAmount:\s*([0-9.]+)/', $html, $a);

        return ['count' => (int) ($c[1] ?? -1), 'amount' => (float) ($a[1] ?? -1)];
    }

    public function test_the_badge_starts_at_zero_on_a_fresh_day(): void
    {
        $this->assertSame(['count' => 0, 'amount' => 0.0], $this->seeded());
    }

    public function test_the_badge_counts_todays_sales_and_their_value(): void
    {
        $product = $this->product(1000);
        $this->sell($product)->assertOk();
        $this->sell($product)->assertOk();

        $this->assertSame(['count' => 2, 'amount' => 2000.0], $this->seeded());
    }

    public function test_voided_sales_are_excluded_like_the_admin_overview(): void
    {
        $product = $this->product(1000);
        $this->sell($product)->assertOk();
        $this->sell($product)->assertOk();

        $sale = Sale::latest('id')->first();
        $this->actingAs($this->cashier)->post("/pos/sales/{$sale->id}/void");

        $this->assertSame(Sale::STATUS_VOIDED, $sale->fresh()->status);
        $this->assertSame(['count' => 1, 'amount' => 1000.0], $this->seeded());
    }

    public function test_yesterdays_sales_do_not_count(): void
    {
        $product = $this->product(1000);
        $this->sell($product)->assertOk();

        Sale::latest('id')->first()->forceFill(['created_at' => now()->subDay()])->save();

        $this->assertSame(['count' => 0, 'amount' => 0.0], $this->seeded());
    }

    /**
     * The screen never reloads between sales, so the badge is incremented in
     * the browser. A replayed idempotency key returns the sale already made -
     * counting that would inflate the day's figures.
     */
    public function test_the_terminal_only_counts_sales_it_actually_made(): void
    {
        $product = $this->product(1000);

        $first = $this->sell($product, 'same-key')->assertOk();
        $replay = $this->sell($product, 'same-key')->assertOk();

        $this->assertNotTrue($first->json('duplicate'));
        $this->assertTrue($replay->json('duplicate'));
        $this->assertSame(1, Sale::count());

        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->getContent();
        $this->assertStringContainsString('if (!result.duplicate)', $html);
    }

    public function test_the_badge_is_rendered_next_to_riders(): void
    {
        $this->product();

        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('x-text="todaySalesCount"', $html);

        // Order in the header: Cylinders, Riders, Sales, Inventory.
        $riders = strpos($html, '>Riders<');
        $sales = strpos($html, 'x-text="todaySalesCount"');
        $inventory = strpos($html, 'x-text="totalInventoryStock"');

        $this->assertNotFalse($riders);
        $this->assertGreaterThan($riders, $sales, 'The Sales badge should follow Riders.');
        $this->assertGreaterThan($sales, $inventory, 'Inventory should still come last.');
    }
}
