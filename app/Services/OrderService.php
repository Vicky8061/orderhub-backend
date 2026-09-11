<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Models\Combo;
use App\Models\MenuItem;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Standard tax rate (e.g. 8.00% = 0.08)
     */
    protected float $taxRate = 0.08;

    /**
     * Create a new order with atomic order number generation and price snapshots.
     */
    public function createOrder(array $data, ?User $cashier = null): Order
    {
        $order = DB::transaction(function () use ($data, $cashier) {
            // 1. Generate daily sequential order number with table lock
            $orderNumber = $this->generateDailyOrderNumber();

            // 2. Pre-calculate items, modifiers, subtotal, and tax
            $subtotal = 0.00;
            $itemsToCreate = [];

            foreach ($data['items'] as $itemData) {
                $isCombo = !empty($itemData['combo_id']);
                $quantity = max(1, (int) ($itemData['quantity'] ?? 1));
                $notes = $itemData['notes'] ?? null;
                $modifierSnapshots = [];

                if ($isCombo) {
                    $combo = Combo::where('id', $itemData['combo_id'])
                        ->where('is_available', true)
                        ->first();

                    if (!$combo) {
                        throw ValidationException::withMessages([
                            'items' => ["Combo ID {$itemData['combo_id']} is invalid or unavailable."],
                        ]);
                    }

                    $unitPrice = (float) $combo->price;
                    $lineTotal = $unitPrice * $quantity;
                    $subtotal += $lineTotal;

                    $itemsToCreate[] = [
                        'combo_id' => $combo->id,
                        'menu_item_id' => null,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'notes' => $notes,
                        'modifiers' => [],
                    ];
                } else {
                    $menuItem = MenuItem::where('id', $itemData['menu_item_id'])
                        ->where('is_available', true)
                        ->first();

                    if (!$menuItem) {
                        throw ValidationException::withMessages([
                            'items' => ["Menu item ID {$itemData['menu_item_id']} is invalid or unavailable."],
                        ]);
                    }

                    $unitPrice = (float) $menuItem->price;
                    $modifiersPriceSum = 0.00;

                    if (!empty($itemData['modifier_ids'])) {
                        $modifiers = Modifier::whereIn('id', $itemData['modifier_ids'])->get();
                        foreach ($modifiers as $modifier) {
                            $priceAdj = (float) $modifier->price_adjustment;
                            $modifiersPriceSum += $priceAdj;
                            $modifierSnapshots[] = [
                                'modifier_id' => $modifier->id,
                                'price_at_order' => $priceAdj,
                            ];
                        }
                    }

                    $itemEffectivePrice = $unitPrice + $modifiersPriceSum;
                    $lineTotal = $itemEffectivePrice * $quantity;
                    $subtotal += $lineTotal;

                    $itemsToCreate[] = [
                        'combo_id' => null,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'notes' => $notes,
                        'modifiers' => $modifierSnapshots,
                    ];
                }
            }

            $taxAmount = round($subtotal * $this->taxRate, 2);
            $total = round($subtotal + $taxAmount, 2);

            // 3. Create the Order header
            $order = Order::create([
                'order_number' => $orderNumber,
                'cashier_id' => $cashier?->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'order_type' => $data['order_type'] ?? 'dine_in',
            ]);

            // 4. Create Order Items and Modifiers
            foreach ($itemsToCreate as $itemInfo) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $itemInfo['menu_item_id'],
                    'combo_id' => $itemInfo['combo_id'],
                    'quantity' => $itemInfo['quantity'],
                    'unit_price' => $itemInfo['unit_price'],
                    'notes' => $itemInfo['notes'],
                ]);

                foreach ($itemInfo['modifiers'] as $modInfo) {
                    OrderItemModifier::create([
                        'order_item_id' => $orderItem->id,
                        'modifier_id' => $modInfo['modifier_id'],
                        'price_at_order' => $modInfo['price_at_order'],
                    ]);
                }
            }

            return $order->load(['items.menuItem', 'items.combo', 'items.modifiers.modifier', 'cashier']);
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    /**
     * Generate an atomic sequential daily order number like ORD-001.
     */
    protected function generateDailyOrderNumber(): string
    {
        $today = Carbon::today();
        
        // Count today's orders with lock to ensure no race conditions
        $todayCount = Order::whereDate('created_at', $today)
            ->lockForUpdate()
            ->count();

        $sequence = $todayCount + 1;
        return sprintf('ORD-%03d', $sequence);
    }

    /**
     * Update order status with validation.
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        $validStatuses = ['pending', 'preparing', 'ready', 'completed', 'cancelled'];

        if (!in_array($newStatus, $validStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status '{$newStatus}'. Allowed: " . implode(', ', $validStatuses)],
            ]);
        }

        $order->status = $newStatus;
        $order->save();

        OrderStatusUpdated::dispatch($order);

        return $order->load(['items.menuItem', 'items.combo', 'items.modifiers.modifier', 'cashier']);
    }
}
