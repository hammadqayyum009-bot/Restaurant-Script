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
