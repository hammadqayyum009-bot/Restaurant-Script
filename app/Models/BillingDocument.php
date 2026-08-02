<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A legally-issued (or still-draft) billing document — quotation, proforma,
 * simplified/standard tax invoice, credit note or delivery note.
 *
 * Once status leaves 'draft' the row is never updated or deleted by any route;
 * see App\Policies\BillingDocumentPolicy. Correction happens only through a
 * credit note (App\Services\Billing\CreditNoteIssuer), which changes only this
 * row's status column.
 */
class BillingDocument extends Model
{
    protected $fillable = [
        'document_type', 'series_year', 'number', 'document_number', 'status',
        'order_id', 'order_number', 'parent_document_id',
        'currency', 'currency_exponent', 'prices_include_vat', 'vat_rate_bp',
        'subtotal_net_minor', 'vat_total_minor', 'grand_total_minor', 'rounding_adjustment_minor',
        'issue_date', 'issued_at', 'issued_by_user_id', 'issued_by_name', 'valid_until',
        'seller_name_en', 'seller_name_ar', 'seller_vat_number', 'seller_cr_number',
        'seller_address_en', 'seller_address_ar', 'seller_phone', 'seller_email', 'seller_logo_path',
        'buyer_name_en', 'buyer_name_ar', 'buyer_vat_number', 'buyer_cr_number',
        'buyer_phone', 'buyer_email', 'buyer_address_en', 'buyer_address_ar',
        'qr_payload', 'hijri_date', 'notes_en', 'notes_ar', 'footer_en', 'footer_ar',
        'credit_reason', 'archived_at',
    ];

    protected $casts = [
        'series_year' => 'integer',
        'number' => 'integer',
        'currency_exponent' => 'integer',
        'prices_include_vat' => 'boolean',
        'vat_rate_bp' => 'integer',
        'subtotal_net_minor' => 'integer',
        'vat_total_minor' => 'integer',
        'grand_total_minor' => 'integer',
        'rounding_adjustment_minor' => 'integer',
        'issue_date' => 'date',
        'issued_at' => 'datetime',
        'valid_until' => 'date',
        'archived_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIALLY_CREDITED = 'partially_credited';

    public const STATUS_FULLY_CREDITED = 'fully_credited';

    public const STATUS_CANCELLED = 'cancelled';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_document_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillingDocumentLine::class, 'document_id')->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BillingDocumentEvent::class, 'document_id')->latest();
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status !== self::STATUS_DRAFT;
    }
}
