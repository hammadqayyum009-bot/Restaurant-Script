<?php

namespace Tests\Feature\Billing;

use App\Models\BillingDocument;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\DocumentIssuer;
use App\Services\Billing\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function orderWithMenuItem(): array
    {
        $category = MenuCategory::create(['name' => 'Mains', 'slug' => 'mains', 'sort_order' => 1]);
        $menuItem = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Original Dish Name',
            'slug' => 'original-dish-name',
            'price' => 50,
            'is_available' => true,
        ]);

        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50]);
        \Database\Factories\OrderItemFactory::new()->create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'name' => 'Original Dish Name',
            'price' => 50,
            'quantity' => 1,
            'line_total' => 50,
        ]);

        return [$order, $menuItem];
    }

    public function test_renaming_a_dish_after_issue_does_not_change_the_issued_document(): void
    {
        [$order, $menuItem] = $this->orderWithMenuItem();
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        $originalLineName = $document->lines->first()->name_en;

        $menuItem->update(['name' => 'Renamed Dish', 'slug' => 'renamed-dish']);

        $document->refresh();
        $this->assertSame($originalLineName, $document->lines->first()->name_en);
        $this->assertSame('Original Dish Name', $document->lines->first()->name_en);
    }

    public function test_repricing_a_dish_after_issue_does_not_change_the_issued_document(): void
    {
        [$order, $menuItem] = $this->orderWithMenuItem();
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        $originalTotal = $document->grand_total_minor;
        $originalUnitPrice = $document->lines->first()->unit_price_minor;

        $menuItem->update(['price' => 999]);

        $document->refresh();
        $this->assertSame($originalTotal, $document->grand_total_minor);
        $this->assertSame($originalUnitPrice, $document->lines->first()->unit_price_minor);
    }

    public function test_changing_seller_settings_after_issue_does_not_change_the_issued_document(): void
    {
        $settings = app(BillingSettings::class);
        $settings->set('billing.seller_name_en', 'Original Seller Name');
        $settings->set('billing.seller_name_ar', 'الاسم الأصلي');

        [$order] = $this->orderWithMenuItem();
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        $this->assertSame('Original Seller Name', $document->seller_name_en);

        $settings->set('billing.seller_name_en', 'Changed Seller Name');
        $settings->set('billing.seller_name_ar', 'الاسم الجديد');

        $document->refresh();
        $this->assertSame('Original Seller Name', $document->seller_name_en, 'The issued document must not pick up the settings change.');
        $this->assertSame('الاسم الأصلي', $document->seller_name_ar);
    }

    public function test_changing_currency_exponent_config_does_not_shift_an_issued_document(): void
    {
        [$order] = $this->orderWithMenuItem();
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        $this->assertSame(2, $document->currency_exponent);
        $originalDisplay = Money::toDecimalFromExponent($document->grand_total_minor, $document->currency_exponent);
        $this->assertSame('50.00', $originalDisplay);

        // Simulate the exponent for this currency changing in config after the
        // document was issued (e.g. AED being redefined, however unlikely).
        config(['billing.currencies.AED' => 3]);

        $document->refresh();

        // The stored column is untouched...
        $this->assertSame(2, $document->currency_exponent, 'currency_exponent is a snapshot column, not derived from config.');

        // ...and rendering via the stored exponent (not a fresh config lookup)
        // must still produce the original figure.
        $this->assertSame(
            $originalDisplay,
            Money::toDecimalFromExponent($document->grand_total_minor, $document->currency_exponent),
        );

        // The currency-code overload, by contrast, WOULD shift — proving the
        // views must use toDecimalFromExponent(), never toDecimal($x, $document->currency).
        $this->assertNotSame($originalDisplay, Money::toDecimal($document->grand_total_minor, 'AED'));
    }

    public function test_deleting_the_linked_order_leaves_the_document_intact_with_its_order_number_snapshot(): void
    {
        [$order] = $this->orderWithMenuItem();
        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $document = app(DocumentIssuer::class)->issue($document, $this->admin());

        $orderNumber = $order->order_number;
        $documentNumber = $document->document_number;

        $order->items()->delete();
        $order->delete();

        $document->refresh();

        $this->assertNull($document->order_id, 'order_id is nullOnDelete.');
        $this->assertSame($orderNumber, $document->order_number, 'order_number survives as a plain snapshot column.');
        $this->assertSame($documentNumber, $document->document_number);
        $this->assertSame(BillingDocument::STATUS_ISSUED, $document->status);
        $this->assertTrue($document->lines->isNotEmpty(), 'The document lines are untouched by the order disappearing.');
    }
}
