@extends('layouts.app')

@php
    $titleKey = match ($outcome) {
        'success' => 'payments.result_success_title',
        'declined' => 'payments.result_declined_title',
        'cancelled' => 'payments.result_cancelled_title',
        'unreachable' => 'payments.result_unreachable_title',
        default => 'payments.result_pending_title',
    };
@endphp

@section('title', __($titleKey).' | '.config('site.name'))

@section('content')
    @php
        $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
        $isCod = $transaction && $transaction->driver === 'cod';
        $bodyKey = match (true) {
            $outcome === 'success' && $isCod => 'payments.result_success_cod_body',
            $outcome === 'success' => 'payments.result_success_body',
            $outcome === 'declined' => 'payments.result_declined_body',
            $outcome === 'cancelled' => 'payments.result_cancelled_body',
            $outcome === 'unreachable' => 'payments.result_unreachable_body',
            default => 'payments.result_pending_body',
        };
    @endphp

    <div dir="{{ $dir }}">
        <section class="section" style="padding-top:80px;">
            <div class="container success-box">
                @if ($outcome === 'success')
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </div>
                @endif

                <h1>{{ __($titleKey) }}</h1>
                <p style="color:var(--ink-500)">{{ __($bodyKey, ['order_number' => $order->order_number]) }}</p>
                <div class="order-number">Order #{{ $order->order_number }}</div>

                @if ($outcome === 'pending')
                    <script>
                        setTimeout(function () { window.location.reload(); }, 15000);
                    </script>
                @endif

                <div style="margin-top:26px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                    @if ($retryUrl)
                        <a href="{{ $retryUrl }}" class="btn btn-primary">{{ __('payments.try_again') }}</a>
                    @endif
                    <a href="{{ route('track.show') }}" class="btn btn-outline on-light">{{ __('payments.track_order') }}</a>
                    <a href="{{ route('home') }}" class="btn btn-outline on-light">Back to Home</a>
                </div>
            </div>
        </section>
    </div>
@endsection
