@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
    <div class="a-card">
        <form method="GET" class="a-filters">
            <input type="search" name="q" value="{{ $search }}" class="a-input" placeholder="Order number, name or phone…">
            <select name="status" class="a-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" {{ $activeStatus === $status ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
            <select name="type" class="a-select">
                <option value="">Delivery &amp; pickup</option>
                <option value="delivery" {{ $activeType === 'delivery' ? 'selected' : '' }}>Delivery</option>
                <option value="pickup" {{ $activeType === 'pickup' ? 'selected' : '' }}>Pickup</option>
            </select>
            <button type="submit" class="a-btn ghost sm">Filter</button>
            @if ($search || $activeStatus || $activeType)
                <a href="{{ route('admin.orders.index') }}" class="a-btn ghost sm">Clear</a>
            @endif
        </form>

        @if ($orders->isEmpty())
            <div class="a-empty"><strong>No orders found</strong>Orders placed on the website appear here straight away.</div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th class="num">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="a-mono"><strong>{{ $order->order_number }}</strong></a>
                                    <div class="a-muted" style="font-size:0.78rem;">
                                        {{ $order->created_at->format('d M Y, H:i') }} &middot; {{ $order->items_count }} items
                                    </div>
                                </td>
                                <td>
                                    {{ $order->customer_name }}
                                    <div class="a-muted" style="font-size:0.78rem;">{{ $order->phone }}</div>
                                </td>
                                <td>{{ ucfirst($order->order_type) }}</td>
                                <td>{{ $order->payment_method === 'cash' ? 'Cash' : 'Card' }}</td>
                                <td>@include('admin.partials.order-status', ['status' => $order->status])</td>
                                <td class="num"><strong>{{ config('site.currency') }} {{ number_format((float) $order->total, 2) }}</strong></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="a-btn ghost sm">Open</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
