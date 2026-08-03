<?php

namespace Tests\Feature\Security;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Regression test: /reservations/success/{id} had no auth and no ownership
 * check, and the route key was the raw auto-increment ID — anyone could walk
 * sequential IDs and read every customer's name, phone, date, and guest
 * count. Fixed with a temporary (24h) signed URL: the redirect after booking
 * now carries a signature, and the bare route requires one.
 */
class ReservationSignedUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sara Al-Amin',
            'phone' => '0500000000',
            'email' => 'sara@example.com',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '19:00',
            'guests' => 2,
            'notes' => '',
        ], $overrides);
    }

    public function test_booking_redirects_to_a_working_signed_confirmation_link(): void
    {
        $response = $this->post(route('reservations.store'), $this->validPayload());

        $reservation = Reservation::first();
        $response->assertRedirect();
        $signedUrl = $response->headers->get('Location');
        $this->assertStringContainsString('signature=', $signedUrl);

        $this->get($signedUrl)->assertOk()->assertSee($reservation->name);
    }

    public function test_the_bare_unsigned_route_is_rejected(): void
    {
        $reservation = Reservation::create($this->validPayload());

        $response = $this->get('/reservations/success/'.$reservation->id);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_swapping_the_id_in_a_valid_signed_url_does_not_grant_access_to_a_different_reservation(): void
    {
        $mine = Reservation::create($this->validPayload(['name' => 'Guest A']));
        $someoneElses = Reservation::create($this->validPayload(['name' => 'Guest B']));

        $myUrl = URL::temporarySignedRoute('reservations.success', now()->addHours(24), ['reservation' => $mine->id]);
        $tampered = str_replace('/reservations/success/'.$mine->id, '/reservations/success/'.$someoneElses->id, $myUrl);

        $response = $this->get($tampered);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_an_expired_signature_is_rejected(): void
    {
        $reservation = Reservation::create($this->validPayload());

        $expiredUrl = URL::temporarySignedRoute('reservations.success', now()->subHour(), ['reservation' => $reservation->id]);

        $response = $this->get($expiredUrl);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }
}
