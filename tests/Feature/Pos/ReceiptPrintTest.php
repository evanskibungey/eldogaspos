<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Printing a receipt.
 *
 * The POS used to print by hiding the whole dashboard with `visibility:
 * hidden` and revealing one div. Hidden elements still occupy layout, so the
 * dashboard's full height kept paginating and every receipt came out on two
 * sheets. The receipt is now a page of its own, loaded into a hidden iframe,
 * so nothing else can paginate with it.
 */
class ReceiptPrintTest extends TestCase
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

    private function soldSale(float $price = 3500): Sale
    {
        $product = Product::factory()->cylinder(12)->withStock(20)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);

        $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertOk();

        return Sale::latest('id')->first();
    }

    public function test_the_receipt_page_renders_the_sale_as_recorded(): void
    {
        $sale = $this->soldSale(3500);

        $this->actingAs($this->cashier)
            ->get("/pos/sales/{$sale->id}/receipt")
            ->assertOk()
            ->assertSee($sale->receipt_number)
            ->assertSee('12kg Gas Cylinder')
            ->assertSee('KSH 3,500')
            // Truncated to 10 characters - 57mm of paper does not fit a long
            // name beside its label without wrapping the row.
            ->assertSee(\Illuminate\Support\Str::limit($this->cashier->name, 10, ''));
    }

    /**
     * The figures must come from the sale row, not from whatever the till had
     * in memory - otherwise a reprint could disagree with the books.
     */
    public function test_the_receipt_survives_a_reprint_long_after_the_sale(): void
    {
        $sale = $this->soldSale(3500);
        $original = $this->actingAs($this->cashier)->get("/pos/sales/{$sale->id}/receipt")->getContent();

        $this->travel(3)->months();

        $reprint = $this->actingAs($this->cashier)->get("/pos/sales/{$sale->id}/receipt")->getContent();

        $this->assertStringContainsString($sale->created_at->format('d/m/Y'), $reprint);
        $this->assertSame(
            substr_count($original, $sale->receipt_number),
            substr_count($reprint, $sale->receipt_number)
        );
    }

    public function test_autoprint_prints_itself_and_hides_the_buttons(): void
    {
        $sale = $this->soldSale();

        $auto = $this->actingAs($this->cashier)
            ->get("/pos/sales/{$sale->id}/receipt?autoprint=1")->assertOk()->getContent();

        $this->assertStringContainsString('window.print()', $auto);
        $this->assertStringNotContainsString('Back to POS', $auto);

        // Visited directly it is a reprint page, not something that ambushes
        // whoever opened it with a print dialog.
        $manual = $this->actingAs($this->cashier)
            ->get("/pos/sales/{$sale->id}/receipt")->assertOk()->getContent();

        $this->assertStringContainsString('Back to POS', $manual);
        $this->assertStringNotContainsString("addEventListener('load'", $manual);
    }

    public function test_the_receipt_is_sized_for_the_thermal_roll(): void
    {
        $sale = $this->soldSale();

        $html = $this->actingAs($this->cashier)
            ->get("/pos/sales/{$sale->id}/receipt")->assertOk()->getContent();

        // `auto` height is what stops the printer feeding a fixed page length
        // and cutting the receipt mid-total.
        $this->assertStringContainsString('size: 57mm auto', $html);
    }

    public function test_a_cashier_cannot_print_someone_elses_sale(): void
    {
        $sale = $this->soldSale();
        $other = User::factory()->cashier()->create();

        $this->actingAs($other)->get("/pos/sales/{$sale->id}/receipt")->assertForbidden();

        // An admin may reprint anyone's.
        $this->actingAs(User::factory()->admin()->create())
            ->get("/pos/sales/{$sale->id}/receipt")->assertOk();
    }

    /**
     * The dashboard must no longer carry its own copy of the receipt. Two
     * templates for one document drift apart, and it was the hidden-div
     * version that produced the two-sheet printouts.
     */
    public function test_the_dashboard_no_longer_prints_itself(): void
    {
        Product::factory()->cylinder(6)->withStock(5)
            ->create(['category_id' => $this->cylinders->id]);

        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('printable-receipt', $html);
        $this->assertStringNotContainsString('@media print', $html);
        $this->assertStringNotContainsString('visibility: hidden', $html);

        // It reaches the receipt by URL instead.
        $this->assertStringContainsString('/receipt?autoprint=1', $html);
    }
}
