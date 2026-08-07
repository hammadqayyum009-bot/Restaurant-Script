<?php

namespace Tests\Feature\Billing;

use App\Models\ActivityLog;
use App\Models\BillingDocument;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Billing\DocumentIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function issuedDocument(): BillingDocument
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 100, 'delivery_fee' => 0, 'tax' => 0, 'total' => 100]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $document = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');

        return app(DocumentIssuer::class)->issue($document, $this->admin());
    }

    protected function draftDocument(): BillingDocument
    {
        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 50, 'delivery_fee' => 0, 'tax' => 0, 'total' => 50]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 50, 'quantity' => 1, 'line_total' => 50]);

        return app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
    }

    public function test_put_on_an_issued_document_returns_403(): void
    {
        $document = $this->issuedDocument();

        $response = $this->actingAs($this->admin())
            ->put(route('admin.billing.update', $document), ['notes_en' => 'nope']);

        $response->assertForbidden();
    }

    public function test_delete_on_an_issued_document_returns_403(): void
    {
        $document = $this->issuedDocument();

        $response = $this->actingAs($this->admin())->delete(route('admin.billing.destroy', $document));

        $response->assertForbidden();
        $this->assertDatabaseHas('billing_documents', ['id' => $document->id]);
    }

    public function test_a_draft_can_be_deleted_by_an_authorised_user(): void
    {
        $document = $this->draftDocument();

        $response = $this->actingAs($this->admin())->delete(route('admin.billing.destroy', $document));

        $response->assertRedirect(route('admin.billing.index'));
        $this->assertDatabaseMissing('billing_documents', ['id' => $document->id]);
    }

    public function test_deleting_a_draft_writes_an_activity_log_row(): void
    {
        $document = $this->draftDocument();
        $countBefore = ActivityLog::count();

        $this->actingAs($this->admin())->delete(route('admin.billing.destroy', $document));

        $this->assertSame($countBefore + 1, ActivityLog::count());
        $this->assertSame('deleted', ActivityLog::latest('id')->first()->action);
    }

    public function test_archiving_hides_a_document_from_the_default_list(): void
    {
        $document = $this->issuedDocument();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.billing.index'))->assertSee($document->document_number);

        $this->actingAs($admin)->post(route('admin.billing.archive', $document));

        $this->actingAs($admin)->get(route('admin.billing.index'))->assertDontSee($document->document_number);
        $this->actingAs($admin)->get(route('admin.billing.index', ['archived' => 1]))->assertSee($document->document_number);
    }

    public function test_archiving_does_not_change_totals_reports_or_the_next_allocated_number(): void
    {
        $document = $this->issuedDocument();
        $totalBefore = $document->grand_total_minor;
        $numberBefore = $document->document_number;

        $this->actingAs($this->admin())->post(route('admin.billing.archive', $document));

        $order = \Database\Factories\OrderFactory::new()->create(['subtotal' => 20, 'delivery_fee' => 0, 'tax' => 0, 'total' => 20]);
        \Database\Factories\OrderItemFactory::new()->create(['order_id' => $order->id, 'price' => 20, 'quantity' => 1, 'line_total' => 20]);
        $nextDraft = app(DocumentIssuer::class)->createDraftFromOrder($order, 'simplified_tax_invoice');
        $nextIssued = app(DocumentIssuer::class)->issue($nextDraft, $this->admin());

        $document->refresh();
        $this->assertSame($totalBefore, $document->grand_total_minor);
        $this->assertSame($numberBefore, $document->document_number);
        $this->assertSame(2, $nextIssued->number, 'Archiving the first document did not free up or skip its number.');
    }

    public function test_every_status_change_logs_an_event_with_actor_timestamp_and_statuses(): void
    {
        $document = $this->issuedDocument();

        $event = $document->events()->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame('draft', $event->from_status);
        $this->assertSame('issued', $event->to_status);
        $this->assertNotNull($event->user_id);
        $this->assertNotNull($event->created_at);
    }
}
