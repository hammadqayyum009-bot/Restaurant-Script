<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: registration, forgot-password, contact, reservations,
 * and reviews had no rate limiting at all — only login did — enabling
 * inbox-bombing via uncapped reset emails, contact/reservation-inbox spam,
 * and mass fake reservations/reviews. Each now shares
 * ThrottlesPublicSubmissions: 5 attempts per 60 seconds, per IP, counting
 * every submission (not just failures — there is no "wrong password"
 * concept for any of these).
 */
class PublicFormThrottlingTest extends TestCase
{
    use RefreshDatabase;

    protected function reservationPayload(): array
    {
        return [
            'name' => 'Guest',
            'phone' => '0500000000',
            'email' => 'guest@example.com',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '19:00',
            'guests' => 2,
        ];
    }

    protected function reviewPayload(): array
    {
        return ['name' => 'Guest', 'rating' => 5, 'comment' => 'Great food.'];
    }

    protected function contactPayload(): array
    {
        return ['name' => 'Guest', 'email' => 'guest@example.com', 'message' => 'Hello there.'];
    }

    /**
     * Deliberately invalid on every attempt (password too short) — a
     * successful registration auto-logs the test client in
     * (AuthController::register() calls Auth::login()), which would trip
     * the `guest` middleware wrapping this route on the very next attempt
     * and mask whether the throttle itself is what's rejecting it.
     */
    protected function invalidRegisterPayload(): array
    {
        return [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
    }

    public function test_registration_is_throttled_after_five_attempts_per_ip(): void
    {
        $payload = $this->invalidRegisterPayload();

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post(route('register'), $payload);
            $response->assertSessionHasErrors('password');
        }

        $response = $this->post(route('register'), $payload);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('email'));
        $this->assertSame(0, User::count(), 'No registration in this test was ever valid.');
    }

    public function test_forgot_password_is_throttled_after_five_attempts_per_ip(): void
    {
        config(['mail.default' => 'smtp']);

        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertSessionMissing('errors');
        }

        $response = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('email'));
    }

    public function test_contact_is_throttled_after_five_attempts_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('contact.store'), $this->contactPayload())->assertSessionMissing('errors');
        }

        $response = $this->post(route('contact.store'), $this->contactPayload());

        $response->assertSessionHasErrors('email');
        $this->assertSame(5, \App\Models\ContactMessage::count());
    }

    public function test_reservations_are_throttled_after_five_attempts_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('reservations.store'), $this->reservationPayload())->assertSessionMissing('errors');
        }

        $response = $this->post(route('reservations.store'), $this->reservationPayload());

        $response->assertSessionHasErrors('email');
        $this->assertSame(5, \App\Models\Reservation::count());
    }

    public function test_reviews_are_throttled_after_five_attempts_per_ip(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('reviews.store'), $this->reviewPayload())->assertSessionMissing('errors');
        }

        $response = $this->post(route('reviews.store'), $this->reviewPayload());

        $response->assertSessionHasErrors('name');
        $this->assertSame(5, \App\Models\Review::count());
    }

    public function test_staying_under_the_limit_on_one_endpoint_does_not_affect_a_different_endpoint(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('contact.store'), $this->contactPayload());
        }
        $this->post(route('contact.store'), $this->contactPayload())->assertSessionHasErrors('email');

        // A different endpoint, same IP, must have its own independent counter.
        $response = $this->post(route('reservations.store'), $this->reservationPayload());
        $response->assertSessionMissing('errors');
    }
}
