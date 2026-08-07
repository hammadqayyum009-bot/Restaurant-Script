<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ActivityLogger;
use App\Services\Mailer;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    public const STATUSES = ['pending', 'confirmed', 'seated', 'cancelled'];

    public function index(Request $request)
    {
        $query = Reservation::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($request->query('when') === 'upcoming') {
            $query->whereDate('reservation_date', '>=', now()->toDateString());
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.reservations.index', [
            'reservations' => $query->orderByDesc('reservation_date')->orderByDesc('reservation_time')
                ->paginate(20)->withQueryString(),
            'statuses' => self::STATUSES,
            'activeStatus' => $status,
            'when' => $request->query('when'),
            'search' => $search,
        ]);
    }

    public function updateStatus(Request $request, Reservation $reservation, Mailer $mailer)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $previous = $reservation->status;
        $changed = $previous !== $data['status'];
        $reservation->update($data);

        if ($changed) {
            $this->activity->log('status',
                'Reservation for '.$reservation->name.': '.$previous.' → '.$reservation->status, $reservation);
        }

        if ($changed && $reservation->email && config('notifications.on_reservation')) {
            $mailer->dispatchTemplate('reservation_status', $reservation->email, $reservation->name, [
                'name' => $reservation->name,
                'guests' => (string) $reservation->guests,
                'date' => $reservation->reservation_date?->format('D, d M Y'),
                'time' => $reservation->reservation_time,
                'status' => $reservation->status,
            ]);
        }

        return back()->with('success', 'Reservation updated'.($changed && $reservation->email ? ' and the guest has been emailed.' : '.'));
    }

    public function destroy(Reservation $reservation)
    {
        $this->activity->deleted('reservation for '.$reservation->name, $reservation);
        $reservation->delete();

        return back()->with('success', 'Reservation deleted.');
    }
}
