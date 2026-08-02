@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)

@section('actions')
    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" rel="noopener" class="a-btn ghost sm">Invoice</a>
    <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" rel="noopener" class="a-btn ghost sm">Receipt</a>
    @can('manage-billing')
        <a href="{{ route('admin.billing.from-order.create', $order) }}" class="a-btn ghost sm">Tax invoice</a>
    @endcan
    <a href="{{ route('admin.orders.index') }}" class="a-btn ghost sm">Back to orders</a>
@endsection

@section('content')
    <div class="a-grid side">
        <div class="a-card">
            <div class="a-card-head">
                <h2>Items</h2>
                <div class="a-actions">@include('admin.partials.order-status', ['status' => $order->status])</div>
            </div>

            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Dish</th>
                            <th class="num">Unit</th>
                            <th class="num">Qty</th>
                            <th class="num">Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td class="num">{{ config('site.currency') }} {{ number_format((float) $item->price, 2) }}</td>
                                <td class="num">{{ $item->quantity }}</td>
                                <td class="num">{{ config('site.currency') }} {{ number_format((float) $item->price * $item->quantity, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="num a-muted">Subtotal</td>
                            <td class="num">{{ config('site.currency') }} {{ number_format((float) $order->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="num a-muted">Delivery fee</td>
                            <td class="num">{{ config('site.currency') }} {{ number_format((float) $order->delivery_fee, 2) }}</td>
                        </tr>
                        @if ($order->tax > 0)
                            <tr>
                                <td colspan="3" class="num a-muted">Tax</td>
                                <td class="num">{{ config('site.currency') }} {{ number_format((float) $order->tax, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="num"><strong>Total</strong></td>
                            <td class="num"><strong>{{ config('site.currency') }} {{ number_format((float) $order->total, 2) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div>
            <div class="a-card">
                <h3>Update status</h3>
                <p class="a-card-sub" style="margin-top:0;">
                    The customer is emailed automatically when this changes.
                </p>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf @method('PUT')
                    <div class="a-field">
                        <select name="status" class="a-select">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="a-btn block">Save status</button>
                </form>
            </div>

            <div class="a-card">
                <h3>Customer</h3>
                <div class="a-stack" style="font-size:0.88rem;">
                    <div><span class="a-muted">Name</span><br>{{ $order->customer_name }}</div>
                    <div><span class="a-muted">Phone</span><br><a href="tel:{{ $order->phone }}">{{ $order->phone }}</a></div>
                    @if ($order->email)
                        <div><span class="a-muted">Email</span><br><a href="mailto:{{ $order->email }}">{{ $order->email }}</a></div>
                    @endif
                    <div><span class="a-muted">{{ $order->order_type === 'pickup' ? 'Pickup' : 'Delivery address' }}</span><br>{{ $order->address }}</div>
                    <div><span class="a-muted">Payment</span><br>{{ $order->payment_method === 'cash' ? 'Cash on delivery' : 'Card on delivery' }}</div>
                    @if ($order->notes)
                        <div><span class="a-muted">Notes</span><br>{{ $order->notes }}</div>
                    @endif
                    <div><span class="a-muted">Placed</span><br>{{ $order->created_at->format('d M Y, H:i') }}</div>
                </div>
            </div>

            <div class="a-card">
                <h3>Danger zone</h3>
                <p class="a-card-sub" style="margin-top:0;">Deleting removes the order and its line items permanently.</p>
                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}"
                      data-confirm="Delete order {{ $order->order_number }}? This cannot be undone.">
                    @csrf @method('DELETE')
                    <button type="submit" class="a-btn danger block">Delete order</button>
                </form>
            </div>
        </div>
    </div>
@endsection
