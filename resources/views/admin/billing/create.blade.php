@extends('admin.layouts.app')

@section('title', 'New standalone document')

@section('actions')
    <a href="{{ route('admin.billing.index') }}" class="a-btn ghost sm">Back to documents</a>
@endsection

@section('content')
    <style>
        .bx-line-row { display: grid; grid-template-columns: 2fr 2fr 0.8fr 1fr 0.9fr auto; gap: 8px; align-items: center; margin-block-end: 8px; }
        @media (max-width: 760px) { .bx-line-row { grid-template-columns: 1fr 1fr; } }
    </style>

    <form method="POST" action="{{ route('admin.billing.store') }}">
        @csrf

        <div class="a-grid side">
            <div>
                <div class="a-card">
                    <div class="a-card-head"><h2>Document type</h2></div>

                    @foreach (['quotation' => 'Quotation', 'proforma' => 'Proforma Invoice', 'delivery_note' => 'Delivery Note'] as $value => $label)
                        <label class="a-check">
                            <input type="radio" name="document_type" value="{{ $value }}" {{ old('document_type', 'quotation') === $value ? 'checked' : '' }}>
                            <span><strong>{{ $label }}</strong></span>
                        </label>
                    @endforeach

                    <div class="a-row cols-2" style="margin-top:10px;">
                        <div class="a-field">
                            <label for="currency">Currency</label>
                            <select id="currency" name="currency" class="a-select">
                                @foreach ($currencies as $code)
                                    <option value="{{ $code }}" {{ old('currency', $defaultCurrency) === $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="a-field">
                            <label for="valid_until">Valid until (optional)</label>
                            <input type="date" id="valid_until" name="valid_until" class="a-input" value="{{ old('valid_until') }}">
                        </div>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Buyer</h2></div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="buyer_name_en">Buyer name (English)</label>
                            <input type="text" id="buyer_name_en" name="buyer_name_en" class="a-input" value="{{ old('buyer_name_en') }}" required>
                        </div>
                        <div class="a-field">
                            <label for="buyer_name_ar">Buyer name (Arabic)</label>
                            <input type="text" id="buyer_name_ar" name="buyer_name_ar" class="a-input" dir="rtl" value="{{ old('buyer_name_ar') }}">
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="buyer_vat_number">Buyer VAT registration number</label>
                            <input type="text" id="buyer_vat_number" name="buyer_vat_number" class="a-input" inputmode="numeric" maxlength="15" value="{{ old('buyer_vat_number') }}" placeholder="15 digits, optional">
                        </div>
                        <div class="a-field">
                            <label for="buyer_cr_number">Buyer C.R. number</label>
                            <input type="text" id="buyer_cr_number" name="buyer_cr_number" class="a-input" value="{{ old('buyer_cr_number') }}">
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="buyer_phone">Phone</label>
                            <input type="text" id="buyer_phone" name="buyer_phone" class="a-input" value="{{ old('buyer_phone') }}">
                        </div>
                        <div class="a-field">
                            <label for="buyer_email">Email</label>
                            <input type="email" id="buyer_email" name="buyer_email" class="a-input" value="{{ old('buyer_email') }}">
                        </div>
                    </div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="buyer_address_en">Address (English)</label>
                            <textarea id="buyer_address_en" name="buyer_address_en" class="a-textarea">{{ old('buyer_address_en') }}</textarea>
                        </div>
                        <div class="a-field">
                            <label for="buyer_address_ar">Address (Arabic)</label>
                            <textarea id="buyer_address_ar" name="buyer_address_ar" class="a-textarea" dir="rtl">{{ old('buyer_address_ar') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Line items</h2></div>

                    <div class="bx-line-row" style="font-weight:600; font-size:0.8rem; color:var(--a-ink-soft);">
                        <div>Description (English)</div>
                        <div>Description (Arabic)</div>
                        <div>Qty</div>
                        <div>Unit price</div>
                        <div>VAT</div>
                        <div></div>
                    </div>

                    <div id="bx-lines">
                        <div class="bx-line-row">
                            <input type="text" name="lines[0][name_en]" class="a-input" placeholder="e.g. Catering package">
                            <input type="text" name="lines[0][name_ar]" class="a-input" dir="rtl">
                            <input type="number" step="0.001" min="0.001" name="lines[0][quantity]" class="a-input" value="1">
                            <input type="number" step="0.01" min="0" name="lines[0][unit_price]" class="a-input" value="0.00">
                            <select name="lines[0][vat_category]" class="a-select">
                                <option value="S">Standard</option>
                                <option value="Z">Zero-rated</option>
                                <option value="E">Exempt</option>
                                <option value="O">Out of scope</option>
                            </select>
                            <button type="button" class="a-btn ghost sm" data-remove-line>&times;</button>
                        </div>
                    </div>

                    <button type="button" class="a-btn ghost sm" id="bx-add-line">Add line</button>
                </div>

                <div class="a-form-actions">
                    <button type="submit" class="a-btn">Create draft</button>
                </div>
            </div>

            <div>
                <div class="a-card">
                    <p class="a-card-sub" style="margin-top:0;">
                        This creates a draft. Nothing is numbered or final until you open it and choose "Issue" —
                        you can review the totals and edit the line items first.
                    </p>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        var host = document.getElementById('bx-lines');
        var addBtn = document.getElementById('bx-add-line');
        var nextIndex = 1;

        addBtn.addEventListener('click', function () {
            var first = host.querySelector('.bx-line-row');
            var clone = first.cloneNode(true);

            clone.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/lines\[\d+\]/, 'lines[' + nextIndex + ']');
                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else if (el.type === 'number') {
                    el.value = el.name.indexOf('[quantity]') !== -1 ? '1' : '0.00';
                } else {
                    el.value = '';
                }
            });

            host.appendChild(clone);
            nextIndex++;
        });

        host.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('[data-remove-line]');
            if (!removeBtn) return;

            if (host.querySelectorAll('.bx-line-row').length > 1) {
                removeBtn.closest('.bx-line-row').remove();
            }
        });
    })();
</script>
@endpush
