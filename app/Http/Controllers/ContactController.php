<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ThrottlesPublicSubmissions;
use App\Models\ContactMessage;
use App\Services\Mailer;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use ThrottlesPublicSubmissions;

    public function store(Request $request, Mailer $mailer)
    {
        $this->ensurePublicSubmissionIsNotRateLimited($request, 'email', 'contact');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = ContactMessage::create($data);

        // The message is already saved, so a mail failure only costs the alert.
        // message is pre-escaped (with line breaks converted) here, so it's
        // marked raw for Mailer::replace() — everything else (name/email/phone)
        // is plain text and gets Mailer's own default escaping.
        if ($admin = config('notifications.admin_email')) {
            $mailer->dispatchTemplate('contact_message', $admin, null, [
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone ?: '—',
                'message' => nl2br(e($message->message)),
            ], rawKeys: ['message']);
        }

        return back()
            ->with('success', 'Thanks for getting in touch — we will reply shortly.')
            ->withFragment('contact-form');
    }
}
