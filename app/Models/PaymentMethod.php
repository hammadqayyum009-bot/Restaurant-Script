<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver',
        'enabled',
        'sort_order',
        'label_en',
        'label_ar',
        'description_en',
        'description_ar',
        'credentials',
        'test_mode',
        'min_order_amount_minor',
        'max_order_amount_minor',
        'allowed_order_types',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'test_mode' => 'boolean',
            'sort_order' => 'integer',
            'min_order_amount_minor' => 'integer',
            'max_order_amount_minor' => 'integer',
            'allowed_order_types' => 'array',
            // Encrypted at rest via Laravel's built-in cipher, keyed from
            // APP_KEY. Never returned by toArray()/toJson() serialization
            // either, since it is also listed in $hidden below.
            'credentials' => 'encrypted:array',
        ];
    }

    protected $hidden = [
        'credentials',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isRestrictedToOrderType(string $orderType): bool
    {
        $allowed = $this->allowed_order_types;

        return ! empty($allowed) && ! in_array($orderType, $allowed, true);
    }

    public function isWithinAmountLimits(int $amountMinor): bool
    {
        if ($this->min_order_amount_minor !== null && $amountMinor < $this->min_order_amount_minor) {
            return false;
        }

        if ($this->max_order_amount_minor !== null && $amountMinor > $this->max_order_amount_minor) {
            return false;
        }

        return true;
    }
}
