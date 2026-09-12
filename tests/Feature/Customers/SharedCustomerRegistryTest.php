<?php

namespace Tests\Feature\Customers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterises how the POS credit flow and the cylinder flow each reach the
 * customer registry.
 *
 * Both read and write the same `customers` table, but they create rows through
 * different code with different duplicate handling, and that difference is
 * visible to users.
 */
class SharedCustomerRegistryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Category $cylinders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cashier = User::factory()->cashier()->create();
        $this->cylinders = Category::factory()->gasCylinders()->create();
    }

    private function product(float $price = 2000): Product
    {
        return Product::factory()->cylinder(6)->withStock(20)
            ->create(['category_id' => $this->cylinders->id, 'price' => $price]);
    }

    private function creditSale(string $name, string $phone)
    {
        return $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $this->product()->id, 'quantity' => 1]],
            'payment_method' => 'credit',
            'customer_details' => ['name' => $name, 'phone' => $phone],
        ]);
    }

    private function cylinderTransaction(array $payload)
    {
        return $this->actingAs($this->admin)->postJson('/admin/cylinders', array_merge([
            'transaction_type' => 'drop_off',
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
            'payment_status' => 'paid',
        ], $payload));
    }

    /*
    |--------------------------------------------------------------------------
    | One registry
    |--------------------------------------------------------------------------
    */

    /**
     * There is a single customers table behind both flows: a customer first
     * seen at the POS is immediately selectable on the cylinder form.
     */
    public function test_a_customer_created_at_the_pos_appears_on_the_cylinder_form(): void
    {
        $this->creditSale('Jane Wanjiku', '0712345678')->assertOk();

        $customer = Customer::where('phone', '0712345678')->first();
        $this->assertNotNull($customer, 'The POS credit flow should create the customer.');

        $this->actingAs($this->admin)
            ->get('/admin/cylinders/create')
            ->assertOk()
            ->assertSee('Jane Wanjiku');
    }

    public function test_a_customer_created_on_the_cylinder_form_is_the_same_record(): void
    {
        $this->cylinderTransaction([
            'customer_name' => 'Peter Otieno',
            'customer_phone' => '0722345678',
        ])->assertOk();

        $customer = Customer::where('phone', '0722345678')->first();

        $this->assertNotNull($customer);
        $this->assertSame('Peter Otieno', $customer->name);

        // And the POS search returns it.
        $this->actingAs($this->cashier)
            ->get('/pos/customers/search?q=Otieno')
            ->assertOk()
            ->assertJsonFragment(['phone' => '0722345678']);
    }

    /*
    |--------------------------------------------------------------------------
    | Divergent duplicate handling
    |--------------------------------------------------------------------------
    */

    /**
     * The POS reuses an existing customer when the phone already exists, which
     * is what keeps one person from becoming several records.
     */
    public function test_the_pos_reuses_an_existing_customer_with_the_same_phone(): void
    {
        $existing = Customer::create([
            'name' => 'Jane Wanjiku',
            'phone' => '0712345678',
            'status' => 'active',
            'credit_limit' => 50000,
        ]);

        $this->creditSale('Jane W', '0712345678')->assertOk();

        $this->assertSame(
            1,
            Customer::where('phone', '0712345678')->count(),
            'The POS should reuse the existing record, not duplicate it.'
        );
        $this->assertSame($existing->id, Customer::where('phone', '0712345678')->first()->id);
    }

    /**
     * The cylinder form now does the same. It used to call create()
     * unconditionally, so an existing phone hit the unique index and the whole
     * transaction was rejected with a raw SQL error.
     */
    public function test_the_cylinder_form_reuses_an_existing_customer(): void
    {
        $existing = Customer::create([
            'name' => 'Jane Wanjiku',
            'phone' => '0712345678',
            'status' => 'active',
        ]);

        $this->cylinderTransaction([
            'customer_name' => 'Jane W',
            'customer_phone' => '0712345678',
        ])->assertOk();

        $this->assertSame(1, Customer::where('phone', '0712345678')->count());
        $this->assertSame(
            $existing->id,
            \App\Models\CylinderTransaction::first()->customer_id,
            'The transaction should attach to the customer already on file.'
        );
    }

    /**
     * The column is free text, so the same person can be stored in several
     * spellings. Matching only the literal string is what let one customer
     * become several records - and what made the second spelling collide with
     * the unique index.
     *
     * @dataProvider equivalentSpellings
     */
    public function test_a_number_matches_however_it_was_typed(string $stored, string $typed): void
    {
        $existing = Customer::create([
            'name' => 'Jane Wanjiku',
            'phone' => $stored,
            'status' => 'active',
        ]);

        $this->cylinderTransaction([
            'customer_name' => 'Jane W',
            'customer_phone' => $typed,
        ])->assertOk();

        $this->assertSame(1, Customer::count(), "'{$typed}' should match the stored '{$stored}'.");
        $this->assertSame($existing->id, \App\Models\CylinderTransaction::first()->customer_id);
    }

    public static function equivalentSpellings(): array
    {
        return [
            'local stored, international typed' => ['0712345678', '+254712345678'],
            'international stored, local typed' => ['254712345678', '0712345678'],
            'plus stored, local typed' => ['+254712345678', '0712345678'],
            'local stored, spaced typed' => ['0712345678', '0712 345 678'],
            'nine digits stored, local typed' => ['712345678', '0712345678'],
        ];
    }

    /**
     * Quick-add used to validate the phone as unique, which turned "this
     * customer already exists" into a validation error at exactly the moment
     * the right answer was to select them.
     */
    public function test_quick_add_returns_the_existing_customer_rather_than_failing(): void
    {
        $existing = Customer::create([
            'name' => 'Jane Wanjiku',
            'phone' => '0712345678',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/customers/quick-create', [
                'name' => 'Jane W',
                'phone' => '+254712345678',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'existing' => true,
                'customer' => ['id' => $existing->id],
            ]);

        $this->assertSame(1, Customer::count());
    }

    public function test_quick_add_still_creates_a_genuinely_new_customer(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/customers/quick-create', [
                'name' => 'New Person',
                'phone' => '0733123456',
            ]);

        $response->assertOk()->assertJson(['success' => true, 'existing' => false]);
        $this->assertSame(1, Customer::where('phone', '0733123456')->count());
    }

    /*
    |--------------------------------------------------------------------------
    | The walk-in placeholder
    |--------------------------------------------------------------------------
    */

    /**
     * Cash sales attach to a shared placeholder customer. It is an active row
     * in the same table, so it used to be offered for selection alongside real
     * people - and a deposit assigned to it belongs to nobody.
     */
    public function test_the_walk_in_placeholder_is_not_offered_for_selection(): void
    {
        $this->actingAs($this->cashier)->postJson('/pos/sales', [
            'cart_items' => [['id' => $this->product()->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertOk();

        $this->assertNotNull(
            Customer::where('phone', '0000000000')->first(),
            'A cash sale creates the placeholder customer.'
        );

        $this->actingAs($this->admin)
            ->get('/admin/cylinders/create')
            ->assertOk()
            ->assertDontSee('0000000000');
    }

    /**
     * @dataProvider customerSearchEndpoints
     */
    public function test_the_walk_in_placeholder_is_excluded_from_search(string $path): void
    {
        Customer::create(['name' => 'Walk-in Customer', 'phone' => '0000000000', 'status' => 'active']);
        Customer::create(['name' => 'Real Person', 'phone' => '0712345678', 'status' => 'active']);

        $this->actingAs($this->admin)
            ->get($path . '?q=')
            ->assertOk()
            ->assertJsonFragment(['phone' => '0712345678'])
            ->assertJsonMissing(['phone' => '0000000000']);
    }

    public static function customerSearchEndpoints(): array
    {
        return [
            'customer search' => ['/admin/customers/search'],
            'cylinder search' => ['/admin/cylinders/search-customers'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | The POS customer picker
    |--------------------------------------------------------------------------
    */

    /**
     * The POS credit picker fetched /api/v1/customers, a path that does not
     * exist - the API is not versioned - so the dropdown was always empty and a
     * cashier could never select an existing customer.
     */
    public function test_the_pos_customer_picker_endpoint_returns_customers(): void
    {
        Customer::create(['name' => 'Jane Wanjiku', 'phone' => '0712345678', 'status' => 'active']);

        $this->actingAs($this->cashier)
            ->get('/pos/customers/search?q=Jane')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Jane Wanjiku']);
    }

    /**
     * The page must request the route that shares its session, not the
     * sanctum-guarded API and not the versioned path that never existed.
     */
    public function test_the_pos_page_requests_the_working_customer_endpoint(): void
    {
        $html = $this->actingAs($this->cashier)->get('/pos/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('/pos/customers/search', $html);
        $this->assertStringNotContainsString('/api/v1/customers', $html);
    }
}
