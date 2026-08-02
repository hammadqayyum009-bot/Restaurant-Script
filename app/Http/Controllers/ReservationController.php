<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\Mailer;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function create()
    {
        return view('reservations.create');
    }

    public function store(Request $request, Mailer $mailer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation = Reservation::create($data);

        $vars = [
            'name' => $reservation->name,
            'phone' => $reservation->phone,
            'guests' => (string) $reservation->guests,
            'date' => $reservation->reservation_date?->format('D, d M Y'),
            'time' => $reservation->reservation_time,
            'notes' => $reservation->notes ?: '—',
        ];

        if ($reservation->email && config('notifications.on_reservation')) {
            $mailer->sendTemplate('reservation', $reservation->email, $reservation->name, $vars);
        }

        if (config('notifications.copy_admin_on_reservation') && config('notifications.admin_email')) {
            $mailer->sendTemplate('admin_reservation', config('notifications.admin_email'), null, $vars);
        }

        return redirect()->route('reservations.success', $reservation->id);
    }

    public function success(Reservation $reservation)
    {
        return view('reservations.success', compact('reservation'));
    }
}
