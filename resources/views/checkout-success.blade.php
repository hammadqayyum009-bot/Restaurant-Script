@extends('layouts.app')

@section('title', 'Order Confirmed | '.config('site.name'))

@section('content')
    <section class="section" style="padding-top:80px;">
        <div class="container success-box">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h1>Thank You, {{ $order->customer_name }}!</h1>
            <p style="color:var(--ink-500)">Your order has been placed successfully. We're preparing your food with care.</p>
            <div class="order-number">Order #{{ $order->order_number }}</div>

            <div class="cart-summary-box text-center" style="text-align:left;">
                <h3>Order Details</h3>
                @foreach ($order->items as $item)
                    <div class="summary-row"><span>{{ $item->quantity }} &times; {{ $item->name }}</span><span>{{ config('site.currency') }} {{ number_format($item->line_total, 2) }}</span></div>
                @endforeach
                <div class="summary-row"><span>Delivery Fee</span><span>{{ config('site.currency') }} {{ number_format($order->delivery_fee, 2) }}</span></div>
                <div class="summary-row total"><span>Total</span><span>{{ config('site.currency') }} {{ number_format($order->total, 2) }}</span></div>
                <p style="font-size:0.85rem; color:var(--ink-500); margin-top:14px;">
                    {{ $order->order_type === 'delivery' ? 'Delivering to: '.$order->address : 'Pickup order' }}<br>
                    Payment: {{ $order->payment_method === 'cash' ? 'Cash on Delivery' : 'Card on Delivery' }}
                </p>
            </div>

            <div style="margin-top:26px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a href="{{ route('home') }}" class="btn btn-outline on-light">Back to Home</a>
                <a href="https://wa.me/{{ config('site.whatsapp') }}?text={{ urlencode('Hi, I just placed order #'.$order->order_number) }}" target="_blank" rel="noopener" class="btn btn-primary">Confirm on WhatsApp</a>
            </div>
        </div>
    </section>
@endsection
