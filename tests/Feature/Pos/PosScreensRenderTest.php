<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke cover for the screens touched by the stock/order-number work, so a
 * broken Blade edit fails here rather than in front of a cashier.
 */
class PosScreensRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();

        $category = Category::factory()->gasCylinders()->create();

        Product::factory()->cylinder(6)->withStock(30)->create(['category_id' => $category->id]);
        // One product entirely reserved, to exercise the out-of-stock path.
        Product::factory()->cylinder(13)->withStock(2, 2)->create(['category_id' => $category->id]);
    }

    /** @test */
    public function the_pos_dashboard_renders(): void
    {
        $this->actingAs($this->cashier)
            ->get('/pos/dashboard')
            ->assertOk()
            ->assertViewHas('products');
    }

    /** @test */
    public function the_dashboard_publishes_sellable_stock_not_physical_stock(): void
    {
        $response = $this->actingAs($this->cashier)->get('/pos/dashboard');

        $products = collect($response->viewData('products'));

        $sixKg = $products->firstWhere('cylinder_size_kg', 6.0);
        $thirteenKg = $products->firstWhere('cylinder_size_kg', 13.0);

        $this->assertSame(30, $sixKg['stock']);
        $this->assertFalse($sixKg['out_of_stock']);
        $this->assertSame('6kg', $sixKg['cylinder_size']);

        // Two on the shelf, both reserved: the terminal must see zero.
        $this->assertSame(0, $thirteenKg['stock']);
        $this->assertSame(2, $thirteenKg['physical_stock']);
        $this->assertSame(2, $thirteenKg['reserved_stock']);
        $this->assertTrue($thirteenKg['out_of_stock']);
    }

    /** @test */
    public function the_stock_check_endpoint_reports_availability(): void
    {
        $thirteenKg = Product::where('cylinder_size_kg', 13)->first();

        $this->actingAs($this->cashier)
            ->postJson('/pos/check-stock', ['product_id' => $thirteenKg->id, 'quantity' => 1])
            ->assertOk()
            ->assertJson([
                'available' => false,
                'current_stock' => 0,
                'physical_stock' => 2,
                'out_of_stock' => true,
            ]);
    }

    /** @test */
    public function sales_history_renders(): void
    {
        $this->actingAs($this->cashier)
            ->get('/pos/sales/history')
            ->assertOk();
    }

    /** @test */
    public function the_sale_creation_screen_renders(): void
    {
        $this->actingAs($this->cashier)
            ->get('/pos/sales/create')
            ->assertOk();
    }
}
