@php
    $tone = match ($status) {
        'completed' => 'ok',
        'cancelled' => 'danger',
        'pending' => 'warn',
        default => 'info',
    };
@endphp
<span class="a-badge {{ $tone }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
