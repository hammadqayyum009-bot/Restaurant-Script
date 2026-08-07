@extends('layouts.app')

@section('title', 'Reservation Confirmed | '.config('site.name'))

@section('content')
    <section class="section" style="padding-top:80px;">
        <div class="container success-box">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </div>
            <h1>Table Reserved, {{ $reservation->name }}!</h1>
            <p style="color:var(--ink-500)">We look forward to welcoming you. Your reservation details are below.</p>

            <div class="cart-summary-box" style="text-align:left;">
                <div class="summary-row"><span>Date</span><span>{{ $reservation->reservation_date->format('d M Y') }}</span></div>
                <div class="summary-row"><span>Time</span><span>{{ \Illuminate\Support\Carbon::parse($reservation->reservation_time)->format('h:i A') }}</span></div>
                <div class="summary-row"><span>Guests</span><span>{{ $reservation->guests }}</span></div>
                <div class="summary-row"><span>Phone</span><span>{{ $reservation->phone }}</span></div>
            </div>

            <div style="margin-top:26px;">
                <a href="{{ route('home') }}" class="btn btn-outline on-light">Back to Home</a>
            </div>
        </div>
    </section>
@endsection
