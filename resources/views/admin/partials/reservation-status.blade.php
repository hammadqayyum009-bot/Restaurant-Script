@php
    $tone = match ($status) {
        'confirmed', 'seated' => 'ok',
        'cancelled' => 'danger',
        default => 'warn',
    };
@endphp
<span class="a-badge {{ $tone }}">{{ ucfirst($status) }}</span>
