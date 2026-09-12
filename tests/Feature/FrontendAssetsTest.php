<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards two failure modes that are invisible from the server side, so nothing
 * in the suite noticed them: scripts pushed to a stack that is never rendered,
 * and fetches aimed at URLs that do not exist.
 */
class FrontendAssetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $category = Category::factory()->gasCylinders()->create();
        Product::factory()->cylinder(6)->withStock(20)
            ->create(['category_id' => $category->id, 'price' => 2000]);
    }

    /**
     * Nine views @push('scripts'). Without a matching @stack every one of those
     * blocks is silently discarded - which is why six Chart.js blocks never ran
     * and every chart canvas in the app rendered blank.
     */
    public function test_the_layout_renders_the_scripts_stack(): void
    {
        $layout = File::get(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString(
            "@stack('scripts')",
            $layout,
            'Without this, every @push(\'scripts\') block in the app is discarded.'
        );
    }

    /**
     * The chart script is pushed from a partial, so it only reaches the browser
     * if the stack is rendered. Asserting on the output is what actually proves
     * the pipeline works end to end.
     */
    public function test_the_admin_dashboard_ships_its_chart_script(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->getContent();

        // The canvases the charts draw into.
        $this->assertStringContainsString('id="salesTrendChart"', $html);

        // And the script that draws them, which lives behind @push.
        $this->assertStringContainsString('cdn.jsdelivr.net/npm/chart.js', $html);
        $this->assertStringContainsString('initializeAdminCharts', $html);
    }

    /**
     * @dataProvider chartPages
     */
    public function test_report_pages_ship_their_chart_script(string $path): void
    {
        $html = $this->actingAs($this->admin)->get($path)->assertOk()->getContent();

        $this->assertStringContainsString(
            'cdn.jsdelivr.net/npm/chart.js',
            $html,
            "{$path} renders a chart canvas but never delivers Chart.js."
        );
    }

    public static function chartPages(): array
    {
        return [
            'inventory report' => ['/admin/reports/inventory'],
            'stock movements report' => ['/admin/reports/stock-movements'],
            'users report' => ['/admin/reports/users'],
        ];
    }

    /**
     * The API is not versioned. Every /api/v1/... fetch in the frontend was a
     * guaranteed 404, and each failed silently in a catch block.
     *
     * @dataProvider pagesThatFetch
     */
    public function test_no_page_fetches_a_versioned_api_path(string $path): void
    {
        $html = $this->actingAs($this->admin)->get($path)->assertOk()->getContent();

        $this->assertStringNotContainsString(
            "fetch('/api/v1/",
            $html,
            "{$path} fetches a versioned API path, which does not exist."
        );
        $this->assertStringNotContainsString('fetch(`/api/v1/', $html);
    }

    public static function pagesThatFetch(): array
    {
        return [
            'pos dashboard' => ['/pos/dashboard'],
            'admin dashboard' => ['/admin/dashboard'],
        ];
    }

    /**
     * The POS component is declared inline in the view. The separate file
     * declared it too and was loaded first, so it was always overwritten -
     * dead weight that still looked editable.
     */
    public function test_the_pos_dashboard_does_not_load_the_shadowed_script(): void
    {
        $html = $this->actingAs($this->admin)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('js/pos-system.js"', $html);

        // The component itself must still be defined, inline.
        $this->assertStringContainsString('function enhancedPosSystem()', $html);
        $this->assertStringContainsString('x-data="enhancedPosSystem()"', $html);
    }
}
