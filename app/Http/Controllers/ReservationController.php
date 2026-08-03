<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ThrottlesPublicSubmissions;
use App\Models\Reservation;
use App\Services\Mailer;
use App\Services\Ordering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ReservationController extends Controller
{
    use ThrottlesPublicSubmissions;

    public function __construct(protected Ordering $ordering)
    {
    }

    public function create()
    {
        if (! $this->ordering->reservationsEnabled()) {
            return redirect()->route('home')
                ->with('error', 'Table bookings are closed at the moment. Please call us instead.');
        }

        return view('reservations.create', ['ordering' => $this->ordering]);
    }

    public function store(Request $request, Mailer $mailer)
    {
        $this->ensurePublicSubmissionIsNotRateLimited($request, 'email', 'reservations');

        if (! $this->ordering->reservationsEnabled()) {
            return redirect()->route('home')
                ->with('error', 'Table bookings are closed at the moment. Please call us instead.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:'.$this->ordering->maxGuests()],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'guests.max' => 'We can seat up to :max guests per booking — call us for larger parties.',
        ]);

        if (! $this->ordering->reservationTimeAllowed($data['reservation_time'])) {
            return back()->withInput()->withErrors([
                'reservation_time' => 'We take bookings between '
                    .$this->ordering->reservationOpensAt().' and '.$this->ordering->reservationClosesAt().'.',
            ]);
        }

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
            $mailer->dispatchTemplate('reservation', $reservation->email, $reservation->name, $vars);
        }

        if (config('notifications.copy_admin_on_reservation') && config('notifications.admin_email')) {
            $mailer->dispatchTemplate('admin_reservation', config('notifications.admin_email'), null, $vars);
        }

        // Signed, not a plain ID-keyed route: this page shows the guest's
        // name, phone, date and headcount, and the auto-increment ID is
        // trivially walkable — a signature is what actually restricts this
        // to whoever was just handed the link, not the URL shape alone.
        return redirect()->to(
            URL::temporarySignedRoute('reservations.success', now()->addHours(24), ['reservation' => $reservation->id])
        );
    }

    public function success(Reservation $reservation)
    {
        return view('reservations.success', compact('reservation'));
    }
}
