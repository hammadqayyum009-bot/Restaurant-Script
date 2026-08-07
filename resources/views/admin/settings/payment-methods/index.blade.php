@extends('admin.layouts.app')

@section('title', __('payments.settings_title'))

@section('content')
    <div class="a-card">
        <div class="a-card-head"><h2>{{ __('payments.settings_title') }}</h2></div>
        <p class="a-card-sub" style="margin-top:0;">{{ __('payments.settings_intro') }}</p>

        <div class="a-table-wrap">
            <table class="a-table" id="payment-methods-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Method</th>
                        <th>Status</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php $method = $row['method']; @endphp
                        <tr draggable="true" data-id="{{ $method->id }}">
                            <td class="a-drag-handle" title="Drag to reorder" aria-hidden="true">&#8942;&#8942;</td>
                            <td>
                                <strong>{{ $method->label_en ?: $row['driver']?->displayInfo()->label ?? $method->driver }}</strong>
                                @if ($method->test_mode)
                                    <span class="a-badge info">{{ __('payments.test_mode') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($method->enabled)
                                    <span class="a-badge ok">{{ __('payments.enabled') }}</span>
                                @else
                                    <span class="a-badge">{{ __('payments.disabled') }}</span>
                                @endif
                                @unless ($row['configured'])
                                    <span class="a-badge warn">{{ __('payments.not_configured') }}</span>
                                @endunless
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.settings.payment-methods.toggle', $method) }}">
                                    @csrf @method('PUT')
                                    <button type="submit" class="a-btn ghost sm">
                                        {{ $method->enabled ? __('payments.disabled') : __('payments.enabled') }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="{{ route('admin.settings.payment-methods.edit', $method) }}" class="a-btn ghost sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="a-card" style="margin-top:18px;">
        <div class="a-card-head"><h2>{{ __('payments.reconciliation_settings_title') }}</h2></div>

        <form method="POST" action="{{ route('admin.settings.payment-methods.settings') }}">
            @csrf @method('PUT')

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="max_attempts_per_order">{{ __('payments.max_attempts_per_order') }}</label>
                    <input type="number" min="1" max="20" id="max_attempts_per_order" name="max_attempts_per_order"
                           class="a-input" value="{{ old('max_attempts_per_order', $maxAttemptsPerOrder) }}" required>
                    @error('max_attempts_per_order')<span class="a-hint" style="color:var(--danger,#c0392b);">{{ $message }}</span>@enderror
                </div>
                <div class="a-field">
                    <label for="stuck_after_minutes">{{ __('payments.stuck_after_minutes') }}</label>
                    <input type="number" min="5" max="1440" id="stuck_after_minutes" name="stuck_after_minutes"
                           class="a-input" value="{{ old('stuck_after_minutes', $stuckAfterMinutes) }}" required>
                    <span class="a-hint">{{ __('payments.stuck_after_minutes_hint') }}</span>
                    @error('stuck_after_minutes')<span class="a-hint" style="color:var(--danger,#c0392b);">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">{{ __('payments.save') }}</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var table = document.getElementById('payment-methods-table');
        if (!table) return;
        var body = table.querySelector('tbody');
        var dragged = null;

        body.addEventListener('dragstart', function (e) {
            if (e.target.tagName !== 'TR') return;
            dragged = e.target;
            e.dataTransfer.effectAllowed = 'move';
        });

        body.addEventListener('dragover', function (e) {
            e.preventDefault();
            var row = e.target.closest('tr');
            if (!row || row === dragged) return;
            var rect = row.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            body.insertBefore(dragged, before ? row : row.nextSibling);
        });

        body.addEventListener('drop', function (e) {
            e.preventDefault();
            var order = Array.prototype.map.call(body.querySelectorAll('tr'), function (row) {
                return row.getAttribute('data-id');
            });

            fetch('{{ route('admin.settings.payment-methods.reorder') }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({order: order}),
            });
        });
    })();
</script>
@endpush
