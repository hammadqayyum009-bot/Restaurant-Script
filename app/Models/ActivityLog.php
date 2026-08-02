<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'description', 'ip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
