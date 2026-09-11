<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KitchenController extends Controller
{
    /**
     * Get active kitchen queue orders (pending & preparing) in FIFO order.
     */
    public function queue(): AnonymousResourceCollection
    {
        $orders = Order::with(['items.menuItem', 'items.combo.menuItems', 'items.modifiers.modifier', 'cashier'])
            ->whereIn('status', ['pending', 'preparing'])
            ->orderBy('created_at', 'asc')
            ->get();

        return OrderResource::collection($orders);
    }
}
