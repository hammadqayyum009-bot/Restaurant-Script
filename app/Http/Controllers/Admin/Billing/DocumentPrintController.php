<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Services\Billing\ZatcaQr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\App;

class DocumentPrintController extends Controller
{
    use AuthorizesRequests;

    /**
     * Only issued documents print — a draft has no number and no QR payload
     * yet, and printing one would produce something that looks official but
     * legally is not.
     */
    public function show(BillingDocument $document)
    {
        $this->authorize('view', $document);

        if ($document->isDraft()) {
            return redirect()
                ->route('admin.billing.show', $document)
                ->with('error', 'Issue the document before printing it.');
        }

        $document->loadMissing('lines');

        // Base64-encoded into a data: URI so the print view can use plain
        // {{ }} escaping — a base64 string contains none of the characters
        // {{ }} would touch, so nothing is lost, and no {!! !!} is needed to
        // put an <svg> on the page.
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode(ZatcaQr::svg((string) $document->qr_payload));

        // Documents are always Arabic-primary (Section B), regardless of the
        // app's own locale (which stays English everywhere else — the admin
        // panel and storefront are not localised). __() in the print view
        // resolves against whatever locale is active when it renders, so it
        // is forced here for the render and restored immediately after.
        $previousLocale = App::getLocale();
        App::setLocale('ar');

        $html = view('documents.print', [
            'document' => $document,
            'qrDataUri' => $qrDataUri,
        ])->render();

        App::setLocale($previousLocale);

        return response($html);
    }
}
