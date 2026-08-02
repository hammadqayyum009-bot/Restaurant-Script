<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingDocumentEvent extends Model
{
    protected $fillable = [
        'document_id', 'user_id', 'user_name', 'from_status', 'to_status', 'reason', 'ip',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
