<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /** Cancelled orders are counted separately and never earn revenue. */
    protected const REVENUE_EXCLUDES = ['cancelled'];

    public const RANGES = [
        'today' => 'Today',
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        'month' => 'This month',
        'custom' => 'Custom range',
    ];

    public function index(Request $request)
    {
        [$from, $to, $range] = $this->resolveRange($request);

        $orders = Order::whereBetween('created_at', [$from, $to]);
        $paidOrders = (clone $orders)->whereNotIn('status', self::REVENUE_EXCLUDES);

        $revenue = (float) (clone $paidOrders)->sum('total');
        $orderCount = (clone $paidOrders)->count();

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'range' => $range,
            'ranges' => self::RANGES,

            'revenue' => $revenue,
            'orderCount' => $orderCount,
            'averageOrder' => $orderCount > 0 ? $revenue / $orderCount : 0.0,
            'cancelled' => (clone $orders)->where('status', 'cancelled')->count(),
            'deliveryFees' => (float) (clone $paidOrders)->sum('delivery_fee'),
            'taxCollected' => (float) (clone $paidOrders)->sum('tax'),
            'reservations' => Reservation::whereBetween('created_at', [$from, $to])->count(),

            'byStatus' => (clone $orders)->selectRaw('status, count(*) as orders, sum(total) as total')
                ->groupBy('status')->orderByDesc('orders')->get(),
            'byType' => (clone $paidOrders)->selectRaw('order_type, count(*) as orders, sum(total) as total')
                ->groupBy('order_type')->get(),
            'byPayment' => (clone $paidOrders)->selectRaw('payment_method, count(*) as orders, sum(total) as total')
                ->groupBy('payment_method')->get(),
            'topDishes' => $this->topDishes($from, $to),
            'series' => $this->dailySeries($from, $to),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolveRange(Request $request): array
    {
        $range = (string) $request->query('range', '30');

        if ($range === 'custom') {
            $from = $this->parseDate($request->query('from')) ?? now()->subDays(30);
            $to = $this->parseDate($request->query('to')) ?? now();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }

            return [$from->startOfDay(), $to->endOfDay(), 'custom'];
        }

        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'today'],
            '7' => [now()->subDays(6)->startOfDay(), now()->endOfDay(), '7'],
            'month' => [now()->startOfMonth(), now()->endOfDay(), 'month'],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay(), '30'],
        };
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    protected function topDishes(Carbon $from, Carbon $to)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', self::REVENUE_EXCLUDES)
            ->selectRaw('order_items.name, sum(order_items.quantity) as qty, sum(order_items.line_total) as revenue')
            ->groupBy('order_items.name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();
    }

    /**
     * One row per day in the range, including days with no orders — a gap in
     * the chart would otherwise read as "no data" rather than "no sales".
     *
     * @return array<int, array{date: string, label: string, revenue: float, orders: int}>
     */
    protected function dailySeries(Carbon $from, Carbon $to): array
    {
        $rows = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', self::REVENUE_EXCLUDES)
            ->selectRaw('date(created_at) as day, sum(total) as revenue, count(*) as orders')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $counts = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', self::REVENUE_EXCLUDES)
            ->selectRaw('date(created_at) as day, count(*) as orders')
            ->groupBy('day')
            ->pluck('orders', 'day');

        $series = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $day->format('d M'),
                'revenue' => round((float) ($rows[$key] ?? 0), 2),
                'orders' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $series;
    }
}
