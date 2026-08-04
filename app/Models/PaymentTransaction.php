<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'driver',
        'amount_minor',
        'currency',
        'status',
        'provider_reference',
        'last_provider_status',
        'failure_reason',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PaymentStatusLog::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }

    public function gatewayLogs(): HasMany
    {
        return $this->hasMany(PaymentGatewayLog::class);
    }
}
