@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    @unless ($mailConfigured)
        <div class="a-alert warn">
            <div>
                <strong>Email is not set up yet.</strong>
                Customers will not receive order or booking confirmations until you
                <a href="{{ route('admin.email.smtp') }}">add your SMTP details</a>.
            </div>
        </div>
    @endunless

    <div class="a-grid cols-4" style="margin-bottom:18px;">
        <div class="a-stat">
            <span class="label">Orders today</span>
            <span class="value">{{ $ordersToday }}</span>
            <span class="meta">{{ $ordersTotal }} all time &middot; {{ $ordersPending }} awaiting action</span>
        </div>
        <div class="a-stat">
            <span class="label">Revenue today</span>
            <span class="value">{{ config('site.currency') }} {{ number_format($revenueToday, 2) }}</span>
            <span class="meta">{{ config('site.currency') }} {{ number_format($revenueTotal, 2) }} all time</span>
        </div>
        <div class="a-stat">
            <span class="label">Reservations</span>
            <span class="value">{{ $reservationsUpcoming }}</span>
            <span class="meta">upcoming &middot; {{ $reservationsPending }} unconfirmed</span>
        </div>
        <div class="a-stat">
            <span class="label">Customers</span>
            <span class="value">{{ $usersTotal }}</span>
            <span class="meta">{{ $dishesTotal }} dishes &middot; {{ $dishesUnavailable }} hidden</span>
        </div>
    </div>

    @if ($messagesUnread)
        <div class="a-alert warn">
            <div>{{ $messagesUnread }} unread message{{ $messagesUnread === 1 ? '' : 's' }} from the contact form.
                <a href="{{ route('admin.messages.index', ['filter' => 'unread']) }}">Read them</a>
            </div>
        </div>
    @endif

    @if ($reviewsPending)
        <div class="a-alert warn">
            <div>{{ $reviewsPending }} review{{ $reviewsPending === 1 ? '' : 's' }} waiting for approval.
                <a href="{{ route('admin.reviews.index', ['filter' => 'pending']) }}">Moderate now</a>
            </div>
        </div>
    @endif

    @if ($stuckPaymentsCount)
        <div class="a-alert warn">
            <div>{{ $stuckPaymentsCount }} {{ __('payments.stuck_transactions_title') }} {{ $stuckPaymentsCount === 1 ? 'needs' : 'need' }} a look.
                <a href="{{ route('admin.payment-transactions.stuck') }}">Review now</a>
            </div>
        </div>
    @endif

    <div class="a-grid side">
        <div class="a-card">
            <div class="a-card-head">
                <h2>Latest orders</h2>
                <div class="a-actions">
                    <a href="{{ route('admin.orders.index') }}" class="a-btn ghost sm">View all</a>
                </div>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="a-empty"><strong>No orders yet</strong>New orders will appear here as they come in.</div>
            @else
                <div class="a-table-wrap">
                    <table class="a-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="num">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}" class="a-mono">{{ $order->order_number }}</a>
                                        <div class="a-muted" style="font-size:0.78rem;">{{ $order->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        {{ $order->customer_name }}
                                        <div class="a-muted" style="font-size:0.78rem;">{{ ucfirst($order->order_type) }}</div>
                                    </td>
                                    <td>@include('admin.partials.order-status', ['status' => $order->status])</td>
                                    <td class="num">{{ config('site.currency') }} {{ number_format((float) $order->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="a-card">
            <div class="a-card-head">
                <h2>Upcoming tables</h2>
                <div class="a-actions">
                    <a href="{{ route('admin.reservations.index') }}" class="a-btn ghost sm">View all</a>
                </div>
            </div>

            @if ($recentReservations->isEmpty())
                <div class="a-empty"><strong>No bookings yet</strong>Table requests will show up here.</div>
            @else
                <div class="a-stack">
                    @foreach ($recentReservations as $reservation)
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                            <div style="flex:1; min-width:0;">
                                <strong style="font-size:0.9rem;">{{ $reservation->name }}</strong>
                                <div class="a-muted" style="font-size:0.8rem;">
                                    {{ $reservation->reservation_date?->format('D, d M') }} &middot;
                                    {{ \Illuminate\Support\Str::of($reservation->reservation_time)->substr(0, 5) }} &middot;
                                    {{ $reservation->guests }} guests
                                </div>
                            </div>
                            @include('admin.partials.reservation-status', ['status' => $reservation->status])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
