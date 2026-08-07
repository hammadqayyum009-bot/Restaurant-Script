@extends('layouts.app')

@section('title', __('payments.redirecting_title').' | '.config('site.name'))

@section('content')
    @php $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr'; @endphp

    <div dir="{{ $dir }}">
        <section class="section" style="padding-top:80px;">
            <div class="container success-box">
                <h1>{{ __('payments.redirecting_title') }}</h1>
                <p style="color:var(--ink-500)">{{ __('payments.redirecting_body') }}</p>

                <div style="margin-top:26px;">
                    <a href="{{ $redirectUrl }}" id="payment-redirect-link" class="btn btn-primary">{{ __('payments.redirecting_button') }}</a>
                </div>
            </div>
        </section>
    </div>

    <meta http-equiv="refresh" content="1;url={{ $redirectUrl }}">
    <script>
        setTimeout(function () {
            window.location.replace(@json($redirectUrl));
        }, 300);
    </script>
@endsection
