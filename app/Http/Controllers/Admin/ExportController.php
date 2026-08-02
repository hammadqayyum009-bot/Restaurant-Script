<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV downloads for the accountant. Rows are streamed and the query is chunked,
 * so a year of orders does not have to fit in memory at once.
 */
class ExportController extends Controller
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    public function orders(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $query = Order::with('items')->whereBetween('created_at', [$from, $to]);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('order_type', $type);
        }

        $currency = config('site.currency');
        $this->activity->log('export', 'Exported orders CSV');

        return $this->stream('orders-'.$from->toDateString().'-to-'.$to->toDateString().'.csv', [
            'Order number', 'Date', 'Customer', 'Phone', 'Email', 'Type', 'Payment', 'Status',
            'Items', 'Subtotal ('.$currency.')', 'Delivery ('.$currency.')', 'Tax ('.$currency.')',
            'Total ('.$currency.')', 'Address', 'Notes',
        ], function ($write) use ($query) {
            $query->orderBy('id')->chunk(200, function ($orders) use ($write) {
                foreach ($orders as $order) {
                    $items = $order->items
                        ->map(fn ($i) => $i->quantity.' x '.$i->name)
                        ->implode('; ');

                    $write([
                        $order->order_number,
                        $order->created_at->format('Y-m-d H:i'),
                        $order->customer_name,
                        $order->phone,
                        $order->email,
                        $order->order_type,
                        $order->payment_method,
                        $order->status,
                        $items,
                        number_format((float) $order->subtotal, 2, '.', ''),
                        number_format((float) $order->delivery_fee, 2, '.', ''),
                        number_format((float) $order->tax, 2, '.', ''),
                        number_format((float) $order->total, 2, '.', ''),
                        $order->address,
                        $order->notes,
                    ]);
                }
            });
        });
    }

    public function reservations(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $query = Reservation::whereBetween('created_at', [$from, $to]);
        $this->activity->log('export', 'Exported reservations CSV');

        return $this->stream('reservations-'.$from->toDateString().'-to-'.$to->toDateString().'.csv', [
            'Guest', 'Phone', 'Email', 'Date', 'Time', 'Guests', 'Status', 'Notes', 'Booked at',
        ], function ($write) use ($query) {
            $query->orderBy('id')->chunk(200, function ($rows) use ($write) {
                foreach ($rows as $r) {
                    $write([
                        $r->name, $r->phone, $r->email,
                        $r->reservation_date?->toDateString(),
                        $r->reservation_time,
                        $r->guests, $r->status, $r->notes,
                        $r->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });
        });
    }

    public function customers(Request $request): StreamedResponse
    {
        $query = User::withCount('orders')->where('is_admin', false);
        $this->activity->log('export', 'Exported customers CSV');

        return $this->stream('customers-'.now()->toDateString().'.csv', [
            'Name', 'Email', 'Phone', 'Orders', 'Active', 'Joined', 'Last login',
        ], function ($write) use ($query) {
            $query->orderBy('id')->chunk(200, function ($users) use ($write) {
                foreach ($users as $u) {
                    $write([
                        $u->name, $u->email, $u->phone, $u->orders_count,
                        $u->is_active ? 'yes' : 'no',
                        $u->created_at->format('Y-m-d'),
                        $u->last_login_at?->format('Y-m-d H:i'),
                    ]);
                }
            });
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function range(Request $request): array
    {
        $range = (string) $request->query('range', '30');

        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'all' => [Carbon::createFromTimestamp(0), now()->endOfDay()],
            'custom' => [
                Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay(),
                Carbon::parse($request->query('to', now()->toDateString()))->endOfDay(),
            ],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * @param  array<int, string>  $headings
     */
    protected function stream(string $filename, array $headings, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');

            // Excel needs the BOM to read UTF-8 names and currency symbols.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings);

            $rows(function (array $row) use ($handle) {
                fputcsv($handle, $row);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
