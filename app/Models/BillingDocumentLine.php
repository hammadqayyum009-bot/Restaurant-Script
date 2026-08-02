<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingDocumentLine extends Model
{
    protected $fillable = [
        'document_id', 'menu_item_id', 'order_item_id', 'source_line_id', 'position',
        'name_en', 'name_ar', 'description_en', 'description_ar',
        'quantity_milli', 'unit_price_minor', 'line_net_minor', 'line_vat_minor',
        'line_total_minor', 'vat_rate_bp', 'vat_category', 'is_delivery_fee',
    ];

    protected $casts = [
        'position' => 'integer',
        'quantity_milli' => 'integer',
        'unit_price_minor' => 'integer',
        'line_net_minor' => 'integer',
        'line_vat_minor' => 'integer',
        'line_total_minor' => 'integer',
        'vat_rate_bp' => 'integer',
        'is_delivery_fee' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class, 'document_id');
    }

    public function sourceLine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_line_id');
    }
}
