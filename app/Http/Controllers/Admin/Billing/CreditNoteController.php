<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Exceptions\Billing\CreditLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreCreditNoteRequest;
use App\Models\BillingDocument;
use App\Services\Billing\CreditNoteIssuer;
use App\Services\Billing\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CreditNoteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected CreditNoteIssuer $issuer)
    {
    }

    public function create(BillingDocument $document)
    {
        $this->authorize('credit', $document);

        $document->loadMissing('lines');

        $lines = $document->lines->map(fn ($line) => [
            'line' => $line,
            'remaining' => Money::milliToDecimal($this->issuer->remainingQuantityMilli($line)),
        ])->filter(fn ($row) => $row['remaining'] > 0)->values();

        return view('admin.billing.credit-note', [
            'document' => $document,
            'lines' => $lines,
            'remainingAmount' => Money::toDecimalFromExponent($this->issuer->remainingAmountMinor($document), $document->currency_exponent),
        ]);
    }

    public function store(StoreCreditNoteRequest $request, BillingDocument $document)
    {
        try {
            $creditNote = $this->issuer->issue($document, $request->user(), $request->validated('reason'), $request->creditedLines());
        } catch (CreditLimitExceededException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.billing.show', $creditNote)->with('success', 'Credit note issued: '.$creditNote->document_number);
    }
}
