<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Mailer;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public const STATUSES = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'];

    public function index(Request $request)
    {
        $query = Order::query()->withCount('items');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('order_type', $type);
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.orders.index', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => self::STATUSES,
            'activeStatus' => $status,
            'activeType' => $type,
            'search' => $search,
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', [
            'order' => $order->load('items'),
            'statuses' => self::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order, Mailer $mailer)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $changed = $order->status !== $data['status'];
        $order->update($data);

        if ($changed && $order->email && config('notifications.on_order_status')) {
            $mailer->sendTemplate('order_status', $order->email, $order->customer_name, [
                'name' => $order->customer_name,
                'order_number' => $order->order_number,
                'status' => str_replace('_', ' ', $order->status),
                'total' => number_format((float) $order->total, 2),
            ]);
        }

        return back()->with('success', 'Order status updated'.($changed && $order->email ? ' and the customer has been emailed.' : '.'));
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted.');
    }
}
