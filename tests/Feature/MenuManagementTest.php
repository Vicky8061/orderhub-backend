<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $kitchen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@orderhub.com')->first();
        $this->cashier = User::where('email', 'cashier@orderhub.com')->first();
        $this->kitchen = User::where('email', 'kitchen@orderhub.com')->first();
    }

    public function test_cashier_can_list_categories_and_menu_items(): void
    {
        $response = $this->actingAs($this->cashier, 'sanctum')
            ->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'display_order']]]);

        $itemResponse = $this->actingAs($this->cashier, 'sanctum')
            ->getJson('/api/menu-items');

        $itemResponse->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'price', 'modifiers']]]);
    }

    public function test_cashier_cannot_create_category(): void
    {
        $response = $this->actingAs($this->cashier, 'sanctum')
            ->postJson('/api/categories', ['name' => 'New Category']);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_menu_item_with_modifiers(): void
    {
        $category = Category::first();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/menu-items', [
                'category_id' => $category->id,
                'name' => 'BBQ Bacon Burger',
                'description' => 'Smoky BBQ with onion rings',
                'price' => 9.49,
                'is_available' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'BBQ Bacon Burger')
            ->assertJsonPath('data.price', 9.49);
    }

    public function test_admin_can_delete_menu_item(): void
    {
        $item = MenuItem::first();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/menu-items/{$item->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }
}
