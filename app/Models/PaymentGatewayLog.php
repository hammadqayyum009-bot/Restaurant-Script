<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'payment_transaction_id',
        'driver',
        'endpoint',
        'http_method',
        'http_status',
        'duration_ms',
        'provider_reference',
        'request_summary',
        'response_summary',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
