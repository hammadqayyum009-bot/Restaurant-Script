@extends('layouts.app')

@section('title', 'Contact Us | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Contact</span>
            <h1>Get in Touch</h1>
            <p>We'd love to hear from you &mdash; reach out for reservations, catering or feedback.</p>
        </div>
    </section>

    <section class="section">
        <div class="container checkout-grid">
            <div>
                <h3 style="margin-bottom:18px;" id="contact-form">Send Us a Message</h3>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST">
                    @csrf
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')<span class="error-text">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone')<span class="error-text">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<span class="error-text">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea class="form-control" id="message" name="message" required>{{ old('message') }}</textarea>
                        @error('message')<span class="error-text">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
            <div>
                <div class="cart-summary-box">
                    <h3>Contact Information</h3>
                    <div class="contact-line">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.1-7-11a7 7 0 1114 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        <span style="color:var(--ink-700)">{{ config('site.address') }}</span>
                    </div>
                    <div class="contact-line">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5h3l1.5 5-2 1.5a12 12 0 006 6l1.5-2 5 1.5v3a1.5 1.5 0 01-1.6 1.5A17.25 17.25 0 013 6.1 1.5 1.5 0 012.25 4.5z"/></svg>
                        <span style="color:var(--ink-700)">{{ config('site.phone') }}</span>
                    </div>
                    <div class="contact-line">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v10.5a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 17.25V6.75zm2-.25l7 6 7-6"/></svg>
                        <span style="color:var(--ink-700)">{{ config('site.email') }}</span>
                    </div>
                    <div class="contact-line">
                        <svg viewBox="0 0 24 24" fill="none" stroke="var(--maroon-800)" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                        <span style="color:var(--ink-700)">{{ config('site.opening_hours') }}</span>
                    </div>
                    <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-block" style="margin-top:16px;">Book a Table</a>
                </div>
            </div>
        </div>
    </section>
@endsection
