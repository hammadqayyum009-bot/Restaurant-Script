@extends('layouts.app')

@section('title', 'Track Your Order | '.config('site.name'))
@section('meta_description', 'Check the status of your '.config('site.name').' order using your order number.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Track Order</span>
            <h1>Track Your Order</h1>
            <p>Enter your order number and the phone or email you ordered with.</p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width:720px;">
            <div class="cart-summary-box" style="position:static;">
                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('track.find') }}">
                    @csrf
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label for="order_number">Order number</label>
                            <input type="text" class="form-control" id="order_number" name="order_number"
                                   value="{{ old('order_number') }}" placeholder="ORD-XXXXXXXX" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="contact">Phone or email</label>
                            <input type="text" class="form-control" id="contact" name="contact"
                                   value="{{ old('contact') }}" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Check status</button>
                </form>
            </div>

            @if ($order)
                @php
                    $stepKeys = array_keys($steps);
                    $currentIndex = array_search($order->status, $stepKeys, true);
                    $cancelled = $order->status === 'cancelled';
                @endphp

                <div class="cart-summary-box" style="position:static; margin-top:24px;">
                    <div class="track-head">
                        <div>
                            <span class="track-label">Order</span>
                            <strong class="order-number" style="margin:0; display:block; font-size:1.25rem;">{{ $order->order_number }}</strong>
                        </div>
                        <div style="text-align:right;">
                            <span class="track-label">Placed</span>
                            <strong>{{ $order->created_at->format('d M Y, H:i') }}</strong>
                        </div>
                    </div>

                    @if ($cancelled)
                        <div class="alert alert-error" style="margin-top:18px;">
                            This order was cancelled. Please call us on {{ config('site.phone') }} if that looks wrong.
                        </div>
                    @else
                        <ol class="track-steps">
                            @foreach ($steps as $key => $label)
                                @php
                                    $index = array_search($key, $stepKeys, true);
                                    $state = $currentIndex === false ? 'todo'
                                        : ($index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'todo'));
                                @endphp
                                <li class="track-step is-{{ $state }}">
                                    <span class="track-dot" aria-hidden="true"></span>
                                    <span class="track-step-label">{{ $label }}</span>
                                    @if ($state === 'current')
                                        <span class="track-now">Now</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    <div class="track-meta">
                        <div>
                            <span class="track-label">{{ $order->order_type === 'pickup' ? 'Collection' : 'Delivering to' }}</span>
                            {{ $order->order_type === 'pickup' ? 'Pickup from the restaurant' : $order->address }}
                        </div>
                        <div>
                            <span class="track-label">Payment</span>
                            {{ $paymentDrivers && $paymentDrivers->has($order->payment_method) ? $paymentDrivers->get($order->payment_method)->displayInfo()->label : ucfirst($order->payment_method) }}
                            @if ($transaction)
                                <span style="font-size:0.82rem; color:var(--ink-500); display:block;">{{ __('payments.status_'.$transaction->status) }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($retryUrl)
                        <a href="{{ $retryUrl }}" class="btn btn-outline on-light btn-block" style="margin-top:14px;">{{ __('payments.retry_link') }}</a>
                    @endif

                    <h3 style="margin-top:22px;">Your items</h3>
                    @foreach ($order->items as $item)
                        <div class="summary-row">
                            <span>{{ $item->quantity }} &times; {{ $item->name }}</span>
                            <span>{{ config('site.currency') }} {{ number_format((float) $item->line_total, 2) }}</span>
                        </div>
                    @endforeach

                    @if ($order->delivery_fee > 0)
                        <div class="summary-row"><span>Delivery</span><span>{{ config('site.currency') }} {{ number_format((float) $order->delivery_fee, 2) }}</span></div>
                    @endif
                    @if ($order->tax > 0)
                        <div class="summary-row"><span>Tax</span><span>{{ config('site.currency') }} {{ number_format((float) $order->tax, 2) }}</span></div>
                    @endif
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>{{ config('site.currency') }} {{ number_format((float) $order->total, 2) }}</span>
                    </div>

                    <a href="https://wa.me/{{ config('site.whatsapp') }}?text={{ rawurlencode('Hi, I have a question about order '.$order->order_number) }}"
                       target="_blank" rel="noopener" class="btn btn-outline on-light btn-block" style="margin-top:18px;">
                        Ask about this order on WhatsApp
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection
