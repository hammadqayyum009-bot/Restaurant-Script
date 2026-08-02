<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per (document_type, series_year). App\Services\Billing\DocumentNumberer
 * is the only code allowed to increment last_number.
 */
class BillingDocumentCounter extends Model
{
    protected $fillable = ['document_type', 'series_year', 'last_number'];

    protected $casts = [
        'series_year' => 'integer',
        'last_number' => 'integer',
    ];
}
