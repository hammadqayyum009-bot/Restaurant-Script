<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Drafts are the only billing documents that can be updated at all — see
 * App\Policies\BillingDocumentPolicy::update(), which denies this once a
 * document is issued. Batch 2 only exposes buyer notes; standalone
 * documents (Batch 3) will extend this with their own line items.
 */
class UpdateDraftDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes_en' => ['nullable', 'string', 'max:600'],
            'notes_ar' => ['nullable', 'string', 'max:600'],
        ];
    }
}
