<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The reason is mandatory and must clear a minimum length — see test 51 —
 * because credit_reason is printed on the document itself (Section G) and
 * "N/A" or a single character is not an auditable correction reason. Which
 * original line each credit line targets is validated here (must belong to
 * the document being credited); over-credit rejection itself — by quantity
 * and by amount — happens in App\Services\Billing\CreditNoteIssuer, since it
 * depends on the running total of every credit note ever issued against
 * this original, not just the shape of this one request.
 *
 * The create form lists every original line with a quantity box, left blank
 * (0) for anything not being credited this time — so quantity is only
 * required to be non-negative here; creditedLines() is what filters that
 * down to the rows actually being credited, and rejects the request if that
 * leaves nothing.
 */
class StoreCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('credit', $this->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $document = $this->route('document');

        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source_line_id' => [
                'required', 'integer',
                Rule::exists('billing_document_lines', 'id')->where('document_id', $document?->id),
            ],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($this->creditedLines())) {
                $validator->errors()->add('lines', 'Enter a quantity greater than zero for at least one line.');
            }
        });
    }

    /**
     * @return array<int, array{source_line_id: int, quantity: string}>
     */
    public function creditedLines(): array
    {
        return collect($this->input('lines', []))
            ->filter(fn ($line) => (float) ($line['quantity'] ?? 0) > 0)
            ->map(fn ($line) => ['source_line_id' => (int) $line['source_line_id'], 'quantity' => (string) $line['quantity']])
            ->values()
            ->all();
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required to issue a credit note.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'lines.required' => 'Select at least one line to credit.',
            'lines.min' => 'Select at least one line to credit.',
            'lines.*.source_line_id.exists' => 'That line does not belong to this document.',
        ];
    }
}
