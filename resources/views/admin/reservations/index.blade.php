@extends('admin.layouts.app')

@section('title', 'Table reservations')

@section('actions')
    <a href="{{ route('admin.export.reservations', ['range' => 'all']) }}" class="a-btn ghost sm">Export CSV</a>
@endsection

@section('content')
    <div class="a-card">
        <form method="GET" class="a-filters">
            <input type="search" name="q" value="{{ $search }}" class="a-input" placeholder="Guest name or phone…">
            <select name="status" class="a-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" {{ $activeStatus === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="when" class="a-select">
                <option value="">All dates</option>
                <option value="upcoming" {{ $when === 'upcoming' ? 'selected' : '' }}>Upcoming only</option>
            </select>
            <button type="submit" class="a-btn ghost sm">Filter</button>
            @if ($search || $activeStatus || $when)
                <a href="{{ route('admin.reservations.index') }}" class="a-btn ghost sm">Clear</a>
            @endif
        </form>

        @if ($reservations->isEmpty())
            <div class="a-empty"><strong>No reservations found</strong>Table requests from the website land here.</div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>When</th>
                            <th class="num">Guests</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservations as $reservation)
                            <tr>
                                <td>
                                    <strong>{{ $reservation->name }}</strong>
                                    <div class="a-muted" style="font-size:0.78rem;">
                                        <a href="tel:{{ $reservation->phone }}">{{ $reservation->phone }}</a>
                                        @if ($reservation->email) &middot; {{ $reservation->email }} @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $reservation->reservation_date?->format('D, d M Y') }}
                                    <div class="a-muted" style="font-size:0.78rem;">{{ Str::of($reservation->reservation_time)->substr(0, 5) }}</div>
                                </td>
                                <td class="num">{{ $reservation->guests }}</td>
                                <td class="a-muted" style="max-width:220px;">{{ Str::limit($reservation->notes, 60) ?: '—' }}</td>
                                <td>@include('admin.partials.reservation-status', ['status' => $reservation->status])</td>
                                <td>
                                    <div class="row-actions" style="flex-wrap:wrap;">
                                        <form method="POST" action="{{ route('admin.reservations.status', $reservation) }}" class="a-inline-form">
                                            @csrf @method('PUT')
                                            <select name="status" class="a-select" style="width:auto; padding:5px 8px; font-size:0.8rem;"
                                                    onchange="this.form.submit()">
                                                @foreach ($statuses as $status)
                                                    <option value="{{ $status }}" {{ $reservation->status === $status ? 'selected' : '' }}>
                                                        {{ ucfirst($status) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}" class="a-inline-form"
                                              data-confirm="Delete this reservation?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="a-btn danger sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $reservations->links() }}</div>
        @endif
    </div>
@endsection
