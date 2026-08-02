@extends('admin.layouts.app')

@section('title', 'Billing documents')

@section('actions')
    @if ($showArchived)
        <a href="{{ route('admin.billing.index') }}" class="a-btn ghost sm">Hide archived</a>
    @else
        <a href="{{ route('admin.billing.index', ['archived' => 1]) }}" class="a-btn ghost sm">Show archived</a>
    @endif
@endsection

@section('content')
    <div class="a-card">
        @if ($documents->isEmpty())
            <div class="a-empty">
                <strong>No billing documents yet</strong>
                Open an order and choose "Tax invoice" to issue your first one.
            </div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Type</th>
                            <th>Order</th>
                            <th>Buyer</th>
                            <th>Status</th>
                            <th class="num">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.billing.show', $document) }}" class="a-mono">
                                        <strong>{{ $document->document_number ?? 'Draft #'.$document->id }}</strong>
                                    </a>
                                    @if ($document->issued_at)
                                        <div class="a-muted" style="font-size:0.78rem;">{{ $document->issued_at->format('d M Y, H:i') }}</div>
                                    @endif
                                </td>
                                <td>{{ __('documents.'.$document->document_type) }}</td>
                                <td>
                                    @if ($document->order_number)
                                        <span class="a-mono">{{ $document->order_number }}</span>
                                    @else
                                        <span class="a-muted">Standalone</span>
                                    @endif
                                </td>
                                <td>{{ $document->buyer_name_en ?: '—' }}</td>
                                <td>
                                    <span class="a-badge {{ $document->status === 'issued' ? 'ok' : ($document->status === 'draft' ? 'warn' : '') }}">
                                        {{ ucwords(str_replace('_', ' ', $document->status)) }}
                                    </span>
                                    @if ($document->archived_at)
                                        <span class="a-badge">Archived</span>
                                    @endif
                                </td>
                                <td class="num">
                                    @if ($document->isIssued())
                                        <strong>{{ $document->currency }} {{ \App\Services\Billing\Money::toDecimal($document->grand_total_minor, $document->currency) }}</strong>
                                    @else
                                        <span class="a-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('admin.billing.show', $document) }}" class="a-btn ghost sm">Open</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $documents->links() }}</div>
        @endif
    </div>
@endsection
