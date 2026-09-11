<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Models\Combo;
use App\Models\MenuItem;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $kitchen;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->cashier = User::where('email', 'cashier@orderhub.com')->first();
        $this->kitchen = User::where('email', 'kitchen@orderhub.com')->first();
        $this->admin = User::where('email', 'admin@orderhub.com')->first();
    }

    public function test_cashier_can_place_order_with_modifiers_and_pricing_is_exact(): void
    {
        // Classic Cheeseburger ($6.99)
        $burger = MenuItem::where('name', 'Classic Cheeseburger')->first();
        // Extra Cheese (+$0.75), Crispy Bacon (+$1.50)
        $extraCheese = Modifier::where('name', 'Extra Cheese')->first();
        $bacon = Modifier::where('name', 'Crispy Bacon')->first();

        // 2 burgers with extra cheese and bacon: (6.99 + 0.75 + 1.50) * 2 = 9.24 * 2 = 18.48
        // Subtotal = 18.48
        // Tax = 18.48 * 0.08 = 1.48 (rounded)
        // Total = 18.48 + 1.48 = 19.96

        $payload = [
            'payment_method' => 'card',
            'order_type' => 'dine_in',
            'items' => [
                [
                    'menu_item_id' => $burger->id,
                    'quantity' => 2,
                    'notes' => 'Extra well done',
                    'modifier_ids' => [$extraCheese->id, $bacon->id],
                ],
            ],
        ];

        $response = $this->actingAs($this->cashier, 'sanctum')
            ->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.order_number', 'ORD-001')
            ->assertJsonPath('data.subtotal', 18.48)
            ->assertJsonPath('data.tax_amount', 1.48)
            ->assertJsonPath('data.total', 19.96)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_subsequent_orders_get_incremented_order_numbers(): void
    {
        $burger = MenuItem::first();

        // Order 1
        $this->actingAs($this->cashier, 'sanctum')->postJson('/api/orders', [
            'payment_method' => 'cash',
            'order_type' => 'takeaway',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 1]],
        ])->assertJsonPath('data.order_number', 'ORD-001');

        // Order 2
        $this->actingAs($this->cashier, 'sanctum')->postJson('/api/orders', [
            'payment_method' => 'cash',
            'order_type' => 'takeaway',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 1]],
        ])->assertJsonPath('data.order_number', 'ORD-002');
    }

    public function test_price_snapshot_survives_future_menu_price_changes(): void
    {
        $burger = MenuItem::first();
        $originalPrice = (float) $burger->price;

        $response = $this->actingAs($this->cashier, 'sanctum')->postJson('/api/orders', [
            'payment_method' => 'cash',
            'order_type' => 'dine_in',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 1]],
        ]);

        $orderId = $response->json('data.id');

        // Admin updates burger price in menu to $99.99
        $burger->price = 99.99;
        $burger->save();

        // Check placed order - snapshot unit_price must still be original
        $check = $this->actingAs($this->cashier, 'sanctum')->getJson("/api/orders/{$orderId}");
        $check->assertStatus(200)
            ->assertJsonPath('data.items.0.unit_price', $originalPrice)
            ->assertJsonPath('data.subtotal', $originalPrice);
    }

    public function test_kitchen_queue_lists_active_orders_and_updates_status(): void
    {
        $burger = MenuItem::first();

        $createResp = $this->actingAs($this->cashier, 'sanctum')->postJson('/api/orders', [
            'payment_method' => 'cash',
            'order_type' => 'dine_in',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 1]],
        ]);
        $orderId = $createResp->json('data.id');

        // Kitchen views queue
        $queueResp = $this->actingAs($this->kitchen, 'sanctum')->getJson('/api/kitchen/queue');
        $queueResp->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Kitchen marks order preparing
        $statusResp = $this->actingAs($this->kitchen, 'sanctum')
            ->patchJson("/api/orders/{$orderId}/status", ['status' => 'preparing']);
        $statusResp->assertStatus(200)->assertJsonPath('data.status', 'preparing');

        // Kitchen marks order ready
        $this->actingAs($this->kitchen, 'sanctum')
            ->patchJson("/api/orders/{$orderId}/status", ['status' => 'ready']);

        // Order is no longer in pending/preparing queue
        $queueAfter = $this->actingAs($this->kitchen, 'sanctum')->getJson('/api/kitchen/queue');
        $queueAfter->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_events_are_broadcast_on_order_creation_and_status_update(): void
    {
        Event::fake([OrderPlaced::class, OrderStatusUpdated::class]);

        $burger = MenuItem::first();

        $createResp = $this->actingAs($this->cashier, 'sanctum')->postJson('/api/orders', [
            'payment_method' => 'cash',
            'order_type' => 'dine_in',
            'items' => [['menu_item_id' => $burger->id, 'quantity' => 1]],
        ]);

        $orderId = $createResp->json('data.id');

        Event::assertDispatched(OrderPlaced::class, function ($event) use ($orderId) {
            return $event->order->id === $orderId;
        });

        $this->actingAs($this->kitchen, 'sanctum')
            ->patchJson("/api/orders/{$orderId}/status", ['status' => 'preparing']);

        Event::assertDispatched(OrderStatusUpdated::class, function ($event) use ($orderId) {
            return $event->order->id === $orderId && $event->order->status === 'preparing';
        });
    }
}
