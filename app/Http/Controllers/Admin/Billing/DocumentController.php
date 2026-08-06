<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Exceptions\Billing\NumberAllocationFailedException;
use App\Exceptions\Billing\ReconciliationFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreOrderDocumentRequest;
use App\Http\Requests\Billing\StoreStandaloneDocumentRequest;
use App\Http\Requests\Billing\UpdateDraftDocumentRequest;
use App\Models\BillingDocument;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\DocumentIssuer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DocumentIssuer $issuer,
        protected ActivityLogger $activity,
    ) {}

    public function index(Request $request)
    {
        $showArchived = $request->boolean('archived');

        $documents = BillingDocument::query()
            ->when(! $showArchived, fn ($q) => $q->whereNull('archived_at'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.billing.index', [
            'documents' => $documents,
            'showArchived' => $showArchived,
        ]);
    }

    public function create()
    {
        $this->authorize('create', BillingDocument::class);

        return view('admin.billing.create', [
            'currencies' => array_keys(config('billing.currencies')),
            'defaultCurrency' => app(BillingSettings::class)->defaultCurrency(),
        ]);
    }

    public function store(StoreStandaloneDocumentRequest $request)
    {
        $this->authorize('create', BillingDocument::class);

        $document = $this->issuer->createDraftStandalone($request->validated());

        return redirect()->route('admin.billing.show', $document)->with('success', 'Draft created. Review it, then issue when ready.');
    }

    public function edit(BillingDocument $document)
    {
        $this->authorize('update', $document);

        if ($document->order_id !== null) {
            return redirect()->route('admin.billing.show', $document);
        }

        $document->loadMissing('lines');

        return view('admin.billing.edit', [
            'document' => $document,
            'currencies' => array_keys(config('billing.currencies')),
        ]);
    }

    public function createFromOrder(Order $order)
    {
        return view('admin.billing.from-order', [
            'order' => $order,
            'existingTaxInvoice' => $this->issuer->issuedTaxInvoiceFor($order),
        ]);
    }

    public function storeFromOrder(StoreOrderDocumentRequest $request, Order $order)
    {
        $this->authorize('create', BillingDocument::class);

        $data = $request->validated();

        // Never trust the create screen alone to have hidden the two
        // tax-invoice options — re-checked here regardless of what was
        // submitted, since this is the actual point a second legal document
        // would get numbered.
        if (in_array($data['document_type'], DocumentIssuer::TAX_INVOICE_TYPES, true)
            && ($existing = $this->issuer->issuedTaxInvoiceFor($order))) {
            return redirect()->route('admin.billing.show', $existing)
                ->with('error', "This order already has an issued tax invoice ({$existing->document_number}). A second one cannot be created for the same order — a credit note corrects an issued one instead.");
        }

        $document = $this->issuer->createDraftFromOrder($order, $data['document_type'], [
            'name_en' => $data['buyer_name_en'] ?? null,
            'name_ar' => $data['buyer_name_ar'] ?? null,
            'vat_number' => $data['buyer_vat_number'] ?? null,
            'cr_number' => $data['buyer_cr_number'] ?? null,
        ]);

        return redirect()->route('admin.billing.show', $document)->with('success', 'Draft created. Review it, then issue when ready.');
    }

    public function show(BillingDocument $document)
    {
        $this->authorize('view', $document);

        $preview = null;
        $reconciliationError = null;

        // Order-linked: nothing to preview once the order itself is gone
        // (order_id nullOnDelete — see the snapshot tests). Standalone: its
        // own lines are always there to preview, order or no order.
        if ($document->isDraft() && ($document->order_id === null || $document->order)) {
            try {
                $preview = $this->issuer->preview($document);
            } catch (ReconciliationFailedException $e) {
                $reconciliationError = $e->getMessage();
            }
        }

        $document->loadMissing('lines', 'events.user', 'order', 'parentDocument', 'creditNotes');

        return view('admin.billing.show', [
            'document' => $document,
            'preview' => $preview,
            'reconciliationError' => $reconciliationError,
        ]);
    }

    public function update(UpdateDraftDocumentRequest $request, BillingDocument $document)
    {
        $data = $request->validated();

        if ($request->isStandalone()) {
            $lines = $data['lines'];
            unset($data['lines']);

            $document->update($data);
            $this->issuer->replaceDraftLines($document, $lines);
        } else {
            $document->update($data);
        }

        $this->activity->log('updated', 'Edited a billing document draft', $document);

        return back()->with('success', 'Draft updated.');
    }

    public function issue(Request $request, BillingDocument $document)
    {
        $this->authorize('issue', $document);

        try {
            $this->issuer->issue($document, $request->user());
        } catch (ReconciliationFailedException $e) {
            return back()->with('error', $e->getMessage());
        } catch (NumberAllocationFailedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.billing.show', $document)->with('success', 'Document issued: '.$document->document_number);
    }

    public function destroy(BillingDocument $document)
    {
        $this->authorize('delete', $document);

        $label = $document->document_type.' draft'.($document->order_number ? ' for order #'.$document->order_number : ' (standalone)');
        $document->delete();

        $this->activity->log('deleted', 'Deleted '.$label);

        return redirect()->route('admin.billing.index')->with('success', 'Draft deleted.');
    }

    public function archive(BillingDocument $document)
    {
        $this->authorize('archive', $document);

        $document->update(['archived_at' => now()]);

        $this->activity->log('updated', 'Archived billing document '.($document->document_number ?? '(draft)'), $document);

        return back()->with('success', 'Document archived.');
    }
}
