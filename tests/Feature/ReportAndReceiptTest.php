<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@orderhub.com')->first();
        $this->cashier = User::where('email', 'cashier@orderhub.com')->first();

        // Create an order for testing
        $burger = MenuItem::first();
        $resp = $this->actingAs($this->cashier, 'sanctum')->postJson('/api/orders', [
            'payment_method' => 'card',
            'order_type' => 'dine_in',
            'items' => [
                ['menu_item_id' => $burger->id, 'quantity' => 2],
            ],
        ]);

        $this->order = Order::find($resp->json('data.id'));
    }

    public function test_admin_can_view_sales_summary(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/sales-summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_orders',
                'subtotal',
                'tax_collected',
                'total_revenue',
                'average_order_value',
            ])
            ->assertJsonPath('total_orders', 1);
    }

    public function test_admin_can_view_best_sellers_and_hourly_revenue(): void
    {
        $bestResp = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/best-sellers');

        $bestResp->assertStatus(200)
            ->assertJsonStructure(['data']);

        $hourResp = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/revenue-by-hour');

        $hourResp->assertStatus(200)
            ->assertJsonStructure(['data' => [['hour', 'order_count', 'revenue']]]);
    }

    public function test_cashier_cannot_view_sales_reports(): void
    {
        $response = $this->actingAs($this->cashier, 'sanctum')
            ->getJson('/api/reports/sales-summary');

        $response->assertStatus(403);
    }

    public function test_receipt_pdf_generation(): void
    {
        $response = $this->actingAs($this->cashier, 'sanctum')
            ->get("/api/orders/{$this->order->id}/receipt");

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }
}
