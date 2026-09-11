<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::with(['items.menuItem', 'items.combo', 'items.modifiers.modifier', 'cashier'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->query('date'));
        }

        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->query('cashier_id'));
        }

        $orders = $query->paginate($request->query('per_page', 20));

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,card,upi'],
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['nullable', 'required_without:items.*.combo_id', 'exists:menu_items,id'],
            'items.*.combo_id' => ['nullable', 'required_without:items.*.menu_item_id', 'exists:combos,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.modifier_ids' => ['nullable', 'array'],
            'items.*.modifier_ids.*' => ['exists:modifiers,id'],
        ]);

        $order = $this->orderService->createOrder($validated, $request->user());

        return response()->json([
            'message' => 'Order placed successfully',
            'data' => new OrderResource($order),
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): OrderResource
    {
        $order->load(['items.menuItem', 'items.combo', 'items.modifiers.modifier', 'cashier']);
        return new OrderResource($order);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,preparing,ready,completed,cancelled'],
        ]);

        $updatedOrder = $this->orderService->updateStatus($order, $validated['status']);

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => new OrderResource($updatedOrder),
        ]);
    }

    /**
     * Cancel/Void order (Admin only).
     */
    public function destroy(Order $order): JsonResponse
    {
        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'message' => 'Order cancelled successfully',
        ]);
    }

    /**
     * Generate printable PDF receipt.
     */
    public function receipt(Order $order): \Illuminate\Http\Response
    {
        $order->load(['items.menuItem', 'items.combo', 'items.modifiers.modifier', 'cashier']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.order', compact('order'))
            ->setPaper([0, 0, 226.77, 566.93], 'portrait');

        return $pdf->stream("receipt-{$order->order_number}.pdf");
    }
}
