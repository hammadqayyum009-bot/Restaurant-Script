@extends('admin.layouts.app')

@section('title', 'Credit note against '.$document->document_number)

@section('actions')
    <a href="{{ route('admin.billing.show', $document) }}" class="a-btn ghost sm">Back to document</a>
@endsection

@section('content')
    <div class="a-grid side">
        <div>
            <form method="POST" action="{{ route('admin.billing.credit.store', $document) }}">
                @csrf

                <div class="a-card">
                    <div class="a-card-head"><h2>Reason for credit</h2></div>
                    <div class="a-field">
                        <label for="reason">Reason</label>
                        <textarea id="reason" name="reason" class="a-textarea" required minlength="10">{{ old('reason') }}</textarea>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Lines to credit</h2></div>
                    <p class="a-card-sub" style="margin-top:0;">
                        Leave a line at 0 to leave it uncredited. Remaining creditable balance on this document: <strong>{{ $remainingAmount }} {{ $document->currency }}</strong>.
                    </p>

                    @if ($lines->isEmpty())
                        <div class="a-alert warn">Nothing left to credit on this document.</div>
                    @else
                        <div class="a-table-wrap">
                            <table class="a-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="num">Remaining qty</th>
                                        <th class="num">Qty to credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $i => $row)
                                        <tr>
                                            <td>
                                                {{ $row['line']->name_en }}
                                                <input type="hidden" name="lines[{{ $i }}][source_line_id]" value="{{ $row['line']->id }}">
                                            </td>
                                            <td class="num">{{ $row['remaining'] }}</td>
                                            <td class="num">
                                                <input type="number" step="0.001" min="0" max="{{ $row['remaining'] }}"
                                                       name="lines[{{ $i }}][quantity]" class="a-input" value="0" style="width:110px;">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($lines->isNotEmpty())
                    <div class="a-form-actions">
                        <button type="submit" class="a-btn">Issue credit note</button>
                    </div>
                @endif
            </form>
        </div>

        <div>
            <div class="a-card">
                <h3>Crediting {{ $document->document_number }}</h3>
                <div class="a-stack" style="font-size:0.86rem;">
                    <div>{{ __('documents.'.$document->document_type) }}</div>
                    <div class="a-muted">Issued {{ optional($document->issue_date)->format('d M Y') }}</div>
                    <div><strong>{{ $document->currency }} {{ \App\Services\Billing\Money::toDecimalFromExponent($document->grand_total_minor, $document->currency_exponent) }}</strong></div>
                </div>
            </div>
            <div class="a-card">
                <p class="a-card-sub" style="margin-top:0;">
                    A credit note is issued immediately — there is no draft step. It gets its own document number
                    and permanently reduces what remains creditable on {{ $document->document_number }}.
                </p>
            </div>
        </div>
    </div>
@endsection
