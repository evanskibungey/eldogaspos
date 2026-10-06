<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page headers reach the screen.
 *
 * Eleven views pass an <x-slot name="header"> and nothing rendered it, so
 * their titles disappeared - and with them the action buttons sitting inside.
 * "Send bulk SMS", "New Category" and "New User" existed only as URLs you had
 * to know. A missing heading is cosmetic; a create button nobody can reach is
 * a feature that may as well not be there.
 */
class PageHeaderSlotTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_the_sms_page_offers_its_compose_button(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/sms')->assertOk()->getContent();

        $this->assertStringContainsString('SMS History', $html);
        $this->assertStringContainsString(route('admin.sms.compose'), $html);
        $this->assertStringContainsString('Send bulk SMS', $html);
    }

    /**
     * Asserted on the header slot's own wording, not on the route.
     *
     * Both of these pages link their create route from elsewhere too - the
     * sidebar for users, an in-page button for categories - so asserting the
     * URL appears would pass with the header still dropped. It did, until this
     * was tightened.
     */
    public function test_the_categories_page_renders_its_header_button(): void
    {
        Category::factory()->gasCylinders()->create();

        $html = $this->actingAs($this->admin)->get('/admin/categories')->assertOk()->getContent();

        $this->assertStringContainsString('Add New Category', $html);
    }

    public function test_the_users_page_renders_its_header(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/users')->assertOk()->getContent();

        // Plural "Users Management" is this slot's wording; the sidebar says
        // "User Management".
        $this->assertStringContainsString('Users Management', $html);
    }

    public function test_the_compose_page_keeps_its_heading(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/sms/compose')->assertOk()->getContent();

        $this->assertStringContainsString('Compose Campaign', $html);
    }
}
