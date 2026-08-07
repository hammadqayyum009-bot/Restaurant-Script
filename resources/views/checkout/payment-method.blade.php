@extends('layouts.app')

@section('title', __('payments.choose_method_title').' | '.config('site.name'))

@section('content')
    @php
        $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
        $labelFor = fn ($method) => app()->getLocale() === 'ar'
            ? ($method->label_ar ?: ($registry->has($method->driver) ? $registry->get($method->driver)->displayInfo()->label : $method->driver))
            : ($method->label_en ?: ($registry->has($method->driver) ? $registry->get($method->driver)->displayInfo()->label : $method->driver));
        $descriptionFor = fn ($method) => app()->getLocale() === 'ar'
            ? ($method->description_ar ?: ($registry->has($method->driver) ? $registry->get($method->driver)->displayInfo()->description : ''))
            : ($method->description_en ?: ($registry->has($method->driver) ? $registry->get($method->driver)->displayInfo()->description : ''));
    @endphp

    <div dir="{{ $dir }}">
        <section class="page-hero">
            <div class="container">
                <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / {{ __('payments.choose_method_title') }}</span>
                <h1>{{ __('payments.choose_method_title') }}</h1>
                <p>{{ __('payments.choose_method_intro', ['order_number' => $order->order_number, 'total' => config('site.currency').' '.number_format((float) $order->total, 2)]) }}</p>
            </div>
        </section>

        <section class="section">
            <div class="container" style="max-width:640px;">
                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="cart-summary-box" style="position:static;">
                    @if ($methods->isEmpty())
                        <div class="alert alert-error">{{ __('payments.no_methods_available') }}</div>
                        <a href="{{ route('track.show') }}" class="btn btn-outline on-light btn-block" style="margin-top:12px;">{{ __('payments.track_order') }}</a>
                    @elseif ($attemptsRemaining <= 0)
                        <div class="alert alert-error">{{ __('payments.retry_cap_reached') }}</div>
                        <a href="{{ route('track.show') }}" class="btn btn-outline on-light btn-block" style="margin-top:12px;">{{ __('payments.track_order') }}</a>
                    @else
                        <form method="POST" action="{{ url()->full() }}">
                            @csrf
                            <div class="radio-cards">
                                @foreach ($methods as $i => $method)
                                    <label>
                                        <input type="radio" name="payment_method_id" value="{{ $method->id }}" {{ $i === 0 ? 'checked' : '' }}>
                                        @if ($method->icon_path)
                                            <img src="{{ asset($method->icon_path) }}" alt="" style="height:20px; width:auto; vertical-align:middle; margin-{{ $dir === 'rtl' ? 'left' : 'right' }}:8px;">
                                        @endif
                                        <strong>{{ $labelFor($method) }}</strong>
                                        @if ($descriptionFor($method))
                                            <div style="font-size:0.82rem; color:var(--ink-500);">{{ $descriptionFor($method) }}</div>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            <p style="font-size:0.82rem; color:var(--ink-500); margin-top:10px;">
                                {{ __('payments.attempts_remaining', ['count' => $attemptsRemaining]) }}
                            </p>

                            <button type="submit" class="btn btn-primary btn-block" style="margin-top:12px;">{{ __('payments.pay_now') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
