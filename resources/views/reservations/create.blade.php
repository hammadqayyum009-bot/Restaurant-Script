@extends('layouts.app')

@section('title', 'Book a Table | '.config('site.name'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <span class="crumbs"><a href="{{ route('home') }}" style="color:var(--gold-400)">Home</a> / Book a Table</span>
            <h1>Book a Table</h1>
            <p>Reserve your table in advance and we'll have it ready for you.</p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width:560px;">
            <div class="cart-summary-box">
                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('reservations.store') }}">
                    @csrf
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email (optional)</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}">
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label for="reservation_date">Date</label>
                            <input type="date" class="form-control" id="reservation_date" name="reservation_date" value="{{ old('reservation_date') }}" min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="reservation_time">Time</label>
                            <input type="time" class="form-control" id="reservation_time" name="reservation_time" value="{{ old('reservation_time', '19:00') }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="guests">Number of Guests</label>
                        <input type="number" class="form-control" id="guests" name="guests" min="1" max="30" value="{{ old('guests', 2) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="notes">Special Requests (optional)</label>
                        <textarea class="form-control" id="notes" name="notes" placeholder="e.g. window seat, birthday celebration...">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Confirm Reservation</button>
                </form>
            </div>
        </div>
    </section>
@endsection
