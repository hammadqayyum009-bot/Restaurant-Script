@extends('admin.layouts.app')

@section('title', 'New tax invoice — Order '.$order->order_number)

@section('actions')
    <a href="{{ route('admin.orders.show', $order) }}" class="a-btn ghost sm">Back to order</a>
@endsection

@section('content')
    <div class="a-grid side">
        <div>
            <form method="POST" action="{{ route('admin.billing.from-order.store', $order) }}">
                @csrf

                <div class="a-card">
                    <div class="a-card-head"><h2>Document type</h2></div>

                    <label class="a-check">
                        <input type="radio" name="document_type" value="simplified_tax_invoice" checked
                               onchange="document.getElementById('buyer-vat-fields').style.display='none'">
                        <span>
                            <strong>Simplified Tax Invoice</strong>
                            <small>For walk-in and delivery customers (B2C). No buyer VAT number needed.</small>
                        </span>
                    </label>
                    <label class="a-check">
                        <input type="radio" name="document_type" value="standard_tax_invoice"
                               onchange="document.getElementById('buyer-vat-fields').style.display=''">
                        <span>
                            <strong>Standard Tax Invoice</strong>
                            <small>For a VAT-registered business buyer (B2B). Requires their VAT number.</small>
                        </span>
                    </label>
                    <label class="a-check">
                        <input type="radio" name="document_type" value="quotation"
                               onchange="document.getElementById('buyer-vat-fields').style.display='none'">
                        <span>
                            <strong>Quotation</strong>
                            <small>A non-binding price quote for this order. No QR code — not a tax invoice.</small>
                        </span>
                    </label>
                    <label class="a-check">
                        <input type="radio" name="document_type" value="proforma"
                               onchange="document.getElementById('buyer-vat-fields').style.display='none'">
                        <span>
                            <strong>Proforma Invoice</strong>
                            <small>A preliminary bill before the sale is final. No QR code — not a tax invoice.</small>
                        </span>
                    </label>
                    <label class="a-check">
                        <input type="radio" name="document_type" value="delivery_note"
                               onchange="document.getElementById('buyer-vat-fields').style.display='none'">
                        <span>
                            <strong>Delivery Note</strong>
                            <small>What's being delivered for this order. No QR code — not a tax invoice.</small>
                        </span>
                    </label>
                </div>

                <div class="a-card">
                    <div class="a-card-head"><h2>Buyer</h2></div>

                    <div class="a-row cols-2">
                        <div class="a-field">
                            <label for="buyer_name_en">Buyer name (English)</label>
                            <input type="text" id="buyer_name_en" name="buyer_name_en" class="a-input"
                                   value="{{ old('buyer_name_en', $order->customer_name) }}">
                        </div>
                        <div class="a-field">
                            <label for="buyer_name_ar">Buyer name (Arabic)</label>
                            <input type="text" id="buyer_name_ar" name="buyer_name_ar" class="a-input" dir="rtl"
                                   value="{{ old('buyer_name_ar') }}">
                        </div>
                    </div>

                    <div id="buyer-vat-fields" style="display:none;">
                        <div class="a-row cols-2">
                            <div class="a-field">
                                <label for="buyer_vat_number">Buyer VAT registration number</label>
                                <input type="text" id="buyer_vat_number" name="buyer_vat_number" class="a-input"
                                       inputmode="numeric" maxlength="15" value="{{ old('buyer_vat_number') }}" placeholder="15 digits">
                            </div>
                            <div class="a-field">
                                <label for="buyer_cr_number">Buyer C.R. number</label>
                                <input type="text" id="buyer_cr_number" name="buyer_cr_number" class="a-input"
                                       value="{{ old('buyer_cr_number') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="a-form-actions">
                    <button type="submit" class="a-btn">Create draft</button>
                </div>
            </form>
        </div>

        <div>
            <div class="a-card">
                <h3>Order {{ $order->order_number }}</h3>
                <div class="a-stack" style="font-size:0.86rem;">
                    <div><strong>{{ $order->customer_name }}</strong></div>
                    <div class="a-muted">{{ $order->phone }}</div>
                    <div class="a-muted">{{ $order->address }}</div>
                    <div style="margin-top:8px;">
                        <strong>{{ config('site.currency') }} {{ number_format((float) $order->total, 2) }}</strong>
                    </div>
                </div>
            </div>
            <div class="a-card">
                <p class="a-card-sub" style="margin-top:0;">
                    This creates a draft. Nothing is numbered or final until you open it and choose "Issue" —
                    you can review the VAT breakdown first.
                </p>
            </div>
        </div>
    </div>
@endsection
