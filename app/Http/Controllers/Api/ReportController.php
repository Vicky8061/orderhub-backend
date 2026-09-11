<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Sales summary metrics (Revenue, Orders, AOV).
     */
    public function salesSummary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::today()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $query = Order::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $totalOrders = (clone $query)->count();
        $totalRevenue = (float) ((clone $query)->sum('total'));
        $subtotal = (float) ((clone $query)->sum('subtotal'));
        $totalTax = (float) ((clone $query)->sum('tax_amount'));
        $averageOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.00;

        $completedOrders = (clone $query)->where('status', 'completed')->count();
        $cancelledOrders = Order::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->where('status', 'cancelled')
            ->count();

        return response()->json([
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'subtotal' => $subtotal,
            'tax_collected' => $totalTax,
            'total_revenue' => $totalRevenue,
            'average_order_value' => $averageOrderValue,
        ]);
    }

    /**
     * Top-selling menu items by quantity sold.
     */
    public function bestSellers(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::today()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());
        $limit = (int) $request->query('limit', 10);

        $bestSellers = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'menu_items.id as menu_item_id',
                'menu_items.name',
                DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as total_sales_amount')
            )
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('total_quantity_sold')
            ->limit($limit)
            ->get();

        return response()->json([
            'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => $bestSellers,
        ]);
    }

    /**
     * Hourly breakdown of revenue and orders for chart visualization.
     */
    public function revenueByHour(Request $request): JsonResponse
    {
        $date = $request->query('date', Carbon::today()->toDateString());

        // Group by hour for SQLite and MySQL compatibility
        $isMysql = DB::connection()->getDriverName() === 'mysql';
        $hourExpression = $isMysql ? 'HOUR(created_at)' : "strftime('%H', created_at)";

        $hourlyData = Order::where(DB::raw('DATE(created_at)'), $date)
            ->where('status', '!=', 'cancelled')
            ->select(
                DB::raw("{$hourExpression} as hour"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy(fn ($item) => (int) $item->hour);

        // Pre-fill all 24 hours so chart always has continuous points
        $chartSeries = [];
        for ($h = 0; $h < 24; $h++) {
            $formattedHour = sprintf('%02d:00', $h);
            $record = $hourlyData->get($h);
            $chartSeries[] = [
                'hour' => $formattedHour,
                'order_count' => $record ? (int) $record->order_count : 0,
                'revenue' => $record ? (float) $record->revenue : 0.00,
            ];
        }

        return response()->json([
            'date' => $date,
            'data' => $chartSeries,
        ]);
    }

    /**
     * Staff performance report (orders & sales per cashier).
     */
    public function staffPerformance(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::today()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $performance = User::leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'orders.cashier_id')
                    ->whereBetween(DB::raw('DATE(orders.created_at)'), [$startDate, $endDate])
                    ->where('orders.status', '!=', 'cancelled');
            })
            ->where('users.role', 'cashier')
            ->select(
                'users.id as cashier_id',
                'users.name as cashier_name',
                'users.email as cashier_email',
                DB::raw('COUNT(orders.id) as orders_processed'),
                DB::raw('COALESCE(SUM(orders.total), 0) as total_sales')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_sales')
            ->get();

        return response()->json([
            'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => $performance,
        ]);
    }
}
