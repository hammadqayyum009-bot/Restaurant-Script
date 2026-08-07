<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'reservation_date', 'reservation_time', 'guests', 'notes', 'status',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];
}
