@extends('admin.layouts.app')

@section('title', $document->document_number ?? 'Draft — '.__('documents.'.$document->document_type))

@section('actions')
    @if ($document->isIssued())
        <a href="{{ route('admin.billing.print', $document) }}" target="_blank" rel="noopener" class="a-btn ghost sm">Print</a>
        @can('credit', $document)
            <a href="{{ route('admin.billing.credit.create', $document) }}" class="a-btn ghost sm">Issue credit note</a>
        @endcan
        @if (! $document->archived_at)
            <form method="POST" action="{{ route('admin.billing.archive', $document) }}" style="display:inline;">
                @csrf
                <button type="submit" class="a-btn ghost sm">Archive</button>
            </form>
        @endif
    @elseif ($document->order_id === null)
        <a href="{{ route('admin.billing.edit', $document) }}" class="a-btn ghost sm">Edit</a>
    @endif
    <a href="{{ route('admin.billing.index') }}" class="a-btn ghost sm">Back to documents</a>
@endsection

@section('content')
    <div class="a-grid side">
        <div>
            @if ($document->isDraft())
                <div class="a-alert warn">
                    This is a draft — nothing has been numbered yet. Nothing here is final until you issue it.
                </div>

                @if ($reconciliationError)
                    <div class="a-alert err">{{ $reconciliationError }}</div>
                @endif
            @endif

            @if ($document->document_type === 'credit_note' && $document->parentDocument)
                <div class="a-alert warn">
                    Credited against
                    <a href="{{ route('admin.billing.show', $document->parentDocument) }}">{{ $document->parentDocument->document_number }}</a>
                    (issued {{ optional($document->parentDocument->issue_date)->format('d M Y') }}).
                    @if ($document->credit_reason)
                        Reason: {{ $document->credit_reason }}
                    @endif
                </div>
            @endif

            @if ($document->creditNotes->isNotEmpty())
                <div class="a-card">
                    <div class="a-card-head"><h2>Credit notes</h2></div>
                    <div class="a-stack" style="font-size:0.86rem;">
                        @foreach ($document->creditNotes as $creditNote)
                            <div>
                                <a href="{{ route('admin.billing.show', $creditNote) }}">{{ $creditNote->document_number }}</a>
                                &middot; {{ $creditNote->currency }} {{ \App\Services\Billing\Money::toDecimalFromExponent($creditNote->grand_total_minor, $creditNote->currency_exponent) }}
                                &middot; {{ optional($creditNote->issue_date)->format('d M Y') }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="a-card">
                <div class="a-card-head"><h2>{{ __('documents.'.$document->document_type) }}</h2></div>

                <div class="a-row cols-2">
                    <div>
                        <div class="a-muted">{{ $document->order_number ? 'Order' : 'Source' }}</div>
                        <div><strong>{{ $document->order_number ?? 'Standalone' }}</strong></div>
                        @if ($document->valid_until)
                            <div class="a-muted" style="font-size:0.8rem;">Valid until {{ $document->valid_until->format('d M Y') }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="a-muted">Buyer</div>
                        <div><strong>{{ $document->buyer_name_en ?: '—' }}</strong></div>
                        @if ($document->buyer_vat_number)
                            <div class="a-muted" style="font-size:0.8rem;">VAT: {{ $document->buyer_vat_number }}</div>
                        @endif
                    </div>
                </div>

                @php $lines = $document->isIssued() ? $document->lines : collect($preview['lines'] ?? []); @endphp

                @if ($lines->isNotEmpty())
                    <div class="a-table-wrap" style="margin-top:14px;">
                        <table class="a-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="num">Qty</th>
                                    <th class="num">Net</th>
                                    <th class="num">VAT</th>
                                    <th class="num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $line)
                                    @php $line = is_array($line) ? (object) $line : $line; @endphp
                                    <tr>
                                        <td>{{ $line->name_en }}</td>
                                        <td class="num">{{ number_format($line->quantity_milli / 1000, $line->quantity_milli % 1000 === 0 ? 0 : 3) }}</td>
                                        <td class="num">{{ \App\Services\Billing\Money::toDecimalFromExponent($line->line_net_minor, $document->currency_exponent) }}</td>
                                        <td class="num">{{ \App\Services\Billing\Money::toDecimalFromExponent($line->line_vat_minor, $document->currency_exponent) }}</td>
                                        <td class="num"><strong>{{ \App\Services\Billing\Money::toDecimalFromExponent($line->line_total_minor, $document->currency_exponent) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php $totals = $document->isIssued() ? $document : (object) $preview; @endphp
                    <div class="a-stack" style="margin-top:12px; align-items:flex-end;">
                        <div>Subtotal: <strong>{{ \App\Services\Billing\Money::toDecimalFromExponent($totals->subtotal_net_minor, $document->currency_exponent) }}</strong></div>
                        <div>VAT: <strong>{{ \App\Services\Billing\Money::toDecimalFromExponent($totals->vat_total_minor, $document->currency_exponent) }}</strong></div>
                        @if (($totals->rounding_adjustment_minor ?? 0) !== 0)
                            <div>Rounding: <strong>{{ \App\Services\Billing\Money::toDecimalFromExponent($totals->rounding_adjustment_minor, $document->currency_exponent) }}</strong></div>
                        @endif
                        <div style="font-size:1.1rem;">Grand total: <strong>{{ $document->currency }} {{ \App\Services\Billing\Money::toDecimalFromExponent($totals->grand_total_minor, $document->currency_exponent) }}</strong></div>
                    </div>
                @endif
            </div>

            @if ($document->isDraft() && ! $reconciliationError)
                <div class="a-card">
                    <div class="a-card-head"><h2>Issue this document</h2></div>
                    <p class="a-card-sub" style="margin-top:0;">
                        Once issued, this document is permanently numbered and cannot be edited or deleted —
                        only corrected with a credit note.
                    </p>
                    <form method="POST" action="{{ route('admin.billing.issue', $document) }}">
                        @csrf
                        <button type="submit" class="a-btn">Issue document</button>
                    </form>
                </div>
            @endif

            @if ($document->isDraft())
                <div class="a-card">
                    <form method="POST" action="{{ route('admin.billing.destroy', $document) }}"
                          onsubmit="return confirm('Delete this draft? This cannot be undone.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="a-btn danger sm">Delete draft</button>
                    </form>
                </div>
            @endif
        </div>

        <div>
            <div class="a-card">
                <h3>Status</h3>
                <span class="a-badge {{ $document->status === 'issued' ? 'ok' : 'warn' }}">
                    {{ ucwords(str_replace('_', ' ', $document->status)) }}
                </span>
                @if ($document->issued_at)
                    <div class="a-muted" style="margin-top:8px; font-size:0.82rem;">
                        Issued {{ $document->issued_at->format('d M Y, H:i') }} by {{ $document->issued_by_name }}
                    </div>
                @endif
            </div>

            @if ($document->events->isNotEmpty())
                <div class="a-card">
                    <h3>History</h3>
                    <div class="a-stack" style="font-size:0.82rem;">
                        @foreach ($document->events as $event)
                            <div>
                                <strong>{{ $event->from_status ?? '—' }} → {{ $event->to_status }}</strong>
                                <div class="a-muted">{{ $event->created_at->format('d M Y, H:i') }} &middot; {{ $event->user_name ?? 'System' }}</div>
                                @if ($event->reason)
                                    <div class="a-muted">{{ $event->reason }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
