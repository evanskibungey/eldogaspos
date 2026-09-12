<?php

namespace Tests\Feature\Cylinders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CylinderTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke cover for every cylinder screen in both contexts.
 *
 * All four list views referenced an undefined $startDate and linked to export
 * and history routes that were never registered, so each returned a 500 in
 * production while the test suite stayed green. Rendering them with real rows
 * is what catches that.
 */
class CylinderScreensRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cashier = User::factory()->cashier()->create();
        $this->customer = Customer::create([
            'name' => 'Jane',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 100000,
        ]);

        $category = Category::factory()->gasCylinders()->create();
        $product = Product::factory()->cylinder(6)->withStock(20)
            ->create(['category_id' => $category->id, 'price' => 2000]);

        // One of each shape the lists filter on, so no screen renders empty
        // and skips the row markup that references the missing variables.
        foreach ([['drop_off', 'paid'], ['drop_off', 'pending'], ['advance_collection', 'pending']] as [$type, $payment]) {
            $this->actingAs($this->admin)->postJson('/admin/cylinders', [
                'transaction_type' => $type,
                'customer_id' => $this->customer->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_status' => $payment,
                'deposit_amount' => $type === 'advance_collection' ? 500 : 0,
            ])->assertOk();
        }
    }

    /**
     * @dataProvider adminScreens
     */
    public function test_admin_cylinder_screens_render(string $path): void
    {
        $this->actingAs($this->admin)->get($path)->assertOk();
    }

    public static function adminScreens(): array
    {
        return [
            'index' => ['/admin/cylinders'],
            'create' => ['/admin/cylinders/create'],
            'paid drop-offs' => ['/admin/cylinders/paid-drop-offs'],
            'unpaid drop-offs' => ['/admin/cylinders/unpaid-drop-offs'],
            'pending payments' => ['/admin/cylinders/pending-payments'],
            'advance collections' => ['/admin/cylinders/advance-collections'],
            'weekly period' => ['/admin/cylinders/pending-payments?period=weekly'],
            'monthly period' => ['/admin/cylinders/pending-payments?period=monthly'],
            'custom range' => ['/admin/cylinders/pending-payments?start_date=2020-01-01&end_date=2099-12-31'],
        ];
    }

    /**
     * @dataProvider posScreens
     */
    public function test_pos_cylinder_screens_render_for_a_cashier(string $path): void
    {
        $this->actingAs($this->cashier)->get($path)->assertOk();
    }

    public static function posScreens(): array
    {
        return [
            'index' => ['/pos/cylinders'],
            'create' => ['/pos/cylinders/create'],
            'paid drop-offs' => ['/pos/cylinders/paid-drop-offs'],
            'unpaid drop-offs' => ['/pos/cylinders/unpaid-drop-offs'],
            'pending payments' => ['/pos/cylinders/pending-payments'],
            'advance collections' => ['/pos/cylinders/advance-collections'],
        ];
    }

    public function test_detail_screens_render(): void
    {
        $transaction = CylinderTransaction::first();

        $this->actingAs($this->admin)->get("/admin/cylinders/{$transaction->id}")->assertOk();
        $this->actingAs($this->admin)->get("/admin/cylinders/{$transaction->id}/edit")->assertOk();
        $this->actingAs($this->admin)->get("/admin/cylinders/{$transaction->id}/receipt")->assertOk();
    }

    public function test_the_customer_profile_and_its_cylinder_history_render(): void
    {
        $this->actingAs($this->admin)->get("/admin/customers/{$this->customer->id}")->assertOk();
        $this->actingAs($this->admin)
            ->get("/admin/cylinders/customer/{$this->customer->id}/history")
            ->assertOk();
    }

    /**
     * @dataProvider exportScreens
     */
    public function test_csv_exports_stream(string $path): void
    {
        $response = $this->actingAs($this->admin)->get($path);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();
        $this->assertStringContainsString('Reference', $body);
    }

    public static function exportScreens(): array
    {
        return [
            'paid drop-offs' => ['/admin/cylinders/paid-drop-offs/export?period=all'],
            'unpaid drop-offs' => ['/admin/cylinders/unpaid-drop-offs/export?period=all'],
            'pending payments' => ['/admin/cylinders/pending-payments/export?period=all'],
            'advance collections' => ['/admin/cylinders/advance-collections/export?period=all'],
        ];
    }

    /**
     * Actions that record money must go through the styled confirmation
     * dialog, never the browser's native confirm(). The dialog is what tells
     * the cashier that completing also books the payment.
     */
    public function test_money_actions_use_the_confirmation_dialog_not_native_confirm(): void
    {
        $pending = CylinderTransaction::query()->pending()->first();
        $this->assertNotNull($pending, 'Setup should leave a pending transaction.');

        foreach (['/admin/cylinders', '/admin/cylinders/pending-payments'] as $path) {
            $html = $this->actingAs($this->admin)->get($path)->assertOk()->getContent();

            $this->assertStringContainsString('id="confirmDialog"', $html, "{$path} is missing the dialog.");
            $this->assertStringContainsString('data-confirm-title', $html, "{$path} has no opted-in form.");
            $this->assertStringNotContainsString('return confirm(', $html, "{$path} still uses native confirm().");
        }
    }

    /**
     * The warning has to name the amount, so completing cannot silently book a
     * payment the customer has not made.
     */
    public function test_the_dialog_warns_that_completing_records_payment(): void
    {
        $html = $this->actingAs($this->admin)
            ->get('/admin/cylinders')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('records payment of KSh', $html);
        $this->assertStringContainsString('mark paid', $html);
    }

    public function test_the_customer_history_export_streams(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/cylinders/customer/{$this->customer->id}/history/export");

        $response->assertOk();
        $this->assertStringContainsString('Reference', $response->streamedContent());
    }
}
