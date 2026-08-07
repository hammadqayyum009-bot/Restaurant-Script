<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for CSV formula injection: every export writes rows built
 * from public-facing, unauthenticated form input (checkout, reservations,
 * registration) straight through fputcsv() with no guard, so a field like
 * "=1+1" or "+HYPERLINK(...)" executes as a formula the moment an admin
 * opens the export in Excel/Sheets.
 */
class CsvExportInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected const PAYLOAD = '=1+1';

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    public function test_orders_export_neutralizes_a_formula_in_the_customer_name(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-CSVTEST01',
            'customer_name' => self::PAYLOAD,
            'phone' => '0500000000',
            'address' => 'Test address',
            'order_type' => 'delivery',
            'subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50,
            'payment_method' => 'cash', 'status' => 'pending',
        ]);
        OrderItem::create(['order_id' => $order->id, 'name' => 'Dish', 'price' => 50, 'quantity' => 1, 'line_total' => 50]);

        $response = $this->actingAs($this->admin())->get(route('admin.export.orders', ['range' => 'all']));

        $csv = $response->streamedContent();
        $this->assertStringNotContainsString(
            "\n=1+1,",
            $csv,
            'A cell beginning with "=" must be neutralized before it reaches the CSV.',
        );
        $this->assertStringContainsString("'".self::PAYLOAD, $csv, 'The value itself must survive, just with a text-forcing prefix.');
    }

    public function test_reservations_export_neutralizes_a_formula_in_the_guest_name(): void
    {
        Reservation::create([
            'name' => '+HYPERLINK("http://evil.example")',
            'phone' => '0500000000',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '19:00',
            'guests' => 2,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.export.reservations', ['range' => 'all']));

        $csv = $response->streamedContent();
        $this->assertStringNotContainsString(
            "\n+HYPERLINK",
            $csv,
            'A cell beginning with "+" must be neutralized before it reaches the CSV.',
        );
        $this->assertStringContainsString("'+HYPERLINK", $csv);
    }

    public function test_customers_export_neutralizes_a_formula_in_the_name(): void
    {
        User::factory()->create(['name' => '@SUM(A1:A9)', 'is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($this->admin())->get(route('admin.export.customers'));

        $csv = $response->streamedContent();
        $this->assertStringNotContainsString(
            "\n@SUM",
            $csv,
            'A cell beginning with "@" must be neutralized before it reaches the CSV.',
        );
        $this->assertStringContainsString("'@SUM", $csv);
    }

    public function test_a_normal_value_is_never_touched(): void
    {
        Reservation::create([
            'name' => 'Sara Al-Amin',
            'phone' => '0500000001',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '19:00',
            'guests' => 2,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.export.reservations', ['range' => 'all']));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Sara Al-Amin', $csv);
        $this->assertStringNotContainsString("'Sara Al-Amin", $csv, 'A value with no leading trigger character must not be prefixed.');
    }
}
