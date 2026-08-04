<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * Every outgoing email goes through here so the admin panel has one place to
 * configure templates and one log to inspect when something does not arrive.
 */
class Mailer
{
    /** Templates the admin can edit, with the defaults used until they do. */
    public const TEMPLATES = [
        'welcome' => [
            'label' => 'Welcome email (new account)',
            'subject' => 'Welcome to {{site_name}}, {{name}}!',
            'body' => "<p>Hi {{name}},</p>\n<p>Thanks for creating an account at <strong>{{site_name}}</strong>. You can now order online, track your orders and book a table in seconds.</p>\n<p>We can't wait to serve you.</p>\n<p>— The {{site_name}} team</p>",
            'vars' => ['name', 'email', 'site_name', 'site_phone', 'site_address'],
        ],
        'order_placed' => [
            'label' => 'Order confirmation (customer)',
            'subject' => 'Order {{order_number}} confirmed — {{site_name}}',
            'body' => "<p>Hi {{name}},</p>\n<p>We've received your order <strong>{{order_number}}</strong> and the kitchen is on it.</p>\n{{order_items}}\n<p><strong>Total: {{currency}} {{total}}</strong><br>Order type: {{order_type}}<br>Payment: {{payment_method}}</p>\n<p>Delivering to: {{address}}</p>\n<p>Questions? Call us on {{site_phone}}.</p>",
            'vars' => ['name', 'order_number', 'total', 'currency', 'order_type', 'payment_method', 'address', 'order_items', 'site_name', 'site_phone'],
        ],
        'order_status' => [
            'label' => 'Order status update',
            'subject' => 'Your order {{order_number}} is now {{status}}',
            'body' => "<p>Hi {{name}},</p>\n<p>Your order <strong>{{order_number}}</strong> has been updated to <strong>{{status}}</strong>.</p>\n<p><strong>Total: {{currency}} {{total}}</strong></p>\n<p>Thanks for ordering from {{site_name}}.</p>",
            'vars' => ['name', 'order_number', 'status', 'total', 'currency', 'site_name'],
        ],
        'reservation' => [
            'label' => 'Table reservation received',
            'subject' => 'Table request received — {{site_name}}',
            'body' => "<p>Hi {{name}},</p>\n<p>We've received your table request for <strong>{{guests}} guests</strong> on <strong>{{date}}</strong> at <strong>{{time}}</strong>.</p>\n<p>We'll confirm shortly. If you need to change anything, call {{site_phone}}.</p>",
            'vars' => ['name', 'guests', 'date', 'time', 'site_name', 'site_phone', 'site_address'],
        ],
        'reservation_status' => [
            'label' => 'Reservation confirmed / cancelled',
            'subject' => 'Your table on {{date}} is {{status}}',
            'body' => "<p>Hi {{name}},</p>\n<p>Your table for <strong>{{guests}}</strong> on <strong>{{date}}</strong> at <strong>{{time}}</strong> is now <strong>{{status}}</strong>.</p>\n<p>See you soon at {{site_name}}.</p>",
            'vars' => ['name', 'guests', 'date', 'time', 'status', 'site_name'],
        ],
        'admin_order' => [
            'label' => 'New order alert (admin copy)',
            'subject' => 'New order {{order_number}} — {{currency}} {{total}}',
            'body' => "<p>A new order has come in.</p>\n<p><strong>{{order_number}}</strong><br>{{name}} — {{phone}}<br>{{order_type}} · {{payment_method}}</p>\n{{order_items}}\n<p><strong>Total: {{currency}} {{total}}</strong></p>\n<p>Address: {{address}}</p>",
            'vars' => ['order_number', 'name', 'phone', 'total', 'currency', 'order_type', 'payment_method', 'address', 'order_items'],
        ],
        'password_reset' => [
            'label' => 'Password reset link',
            'subject' => 'Reset your {{site_name}} password',
            'body' => "<p>Hi {{name}},</p>\n<p>We received a request to reset your password. Use the link below — it works for {{expires_in}}.</p>\n<p><a href=\"{{reset_url}}\">Reset my password</a></p>\n<p>If you did not ask for this, you can ignore this email; nothing has changed.</p>",
            'vars' => ['name', 'reset_url', 'expires_in', 'site_name'],
        ],
        'contact_message' => [
            'label' => 'Contact form message (admin copy)',
            'subject' => 'New message from {{name}} — {{site_name}}',
            'body' => "<p><strong>{{name}}</strong> sent a message through the website.</p>\n<p>Email: {{email}}<br>Phone: {{phone}}</p>\n<p>{{message}}</p>",
            'vars' => ['name', 'email', 'phone', 'message', 'site_name'],
        ],
        'admin_reservation' => [
            'label' => 'New reservation alert (admin copy)',
            'subject' => 'New table request — {{date}} {{time}}',
            'body' => "<p>{{name}} ({{phone}}) requested a table for <strong>{{guests}}</strong> on <strong>{{date}}</strong> at <strong>{{time}}</strong>.</p>\n<p>Notes: {{notes}}</p>",
            'vars' => ['name', 'phone', 'guests', 'date', 'time', 'notes'],
        ],
        'payment_security_alert' => [
            'label' => 'Payment amount/currency mismatch (admin alert)',
            'subject' => 'Payment mismatch on order #{{order_id}} — action needed',
            'body' => "<p>A payment provider returned an amount or currency that did not match our records. The transaction was <strong>not</strong> marked paid.</p>\n<p>Transaction: {{transaction_id}}<br>Order: #{{order_id}}</p>\n<p>{{detail}}</p>\n<p>Check the payment transaction's log in the admin panel.</p>",
            'vars' => ['transaction_id', 'order_id', 'detail'],
        ],
    ];

    public function __construct(protected Settings $settings)
    {
    }

    public function templateSubject(string $key): string
    {
        return (string) $this->settings->get('tpl_'.$key.'_subject', self::TEMPLATES[$key]['subject'] ?? '');
    }

    public function templateBody(string $key): string
    {
        return (string) $this->settings->get('tpl_'.$key.'_body', self::TEMPLATES[$key]['body'] ?? '');
    }

    /**
     * Queues one of the editable templates to go out once the response has
     * been returned to the browser. Shared hosting has no queue worker, so
     * this runs in the same request — it just stops a slow SMTP handshake from
     * holding up a checkout or a booking.
     *
     * @param  array<string, string|null>  $vars
     * @param  array<int, string>  $sensitiveKeys  Keys in $vars (e.g. a
     *     password-reset link) that must never reach email_logs.body in the
     *     clear — see sendTemplate().
     * @param  array<int, string>  $rawKeys  Keys in $vars that are already
     *     safe, pre-built HTML (e.g. an order's line-item list) and must not
     *     be escaped a second time — see sendTemplate().
     */
    public function dispatchTemplate(string $key, string $toEmail, ?string $toName, array $vars, array $sensitiveKeys = [], array $rawKeys = []): void
    {
        dispatch(function () use ($key, $toEmail, $toName, $vars, $sensitiveKeys, $rawKeys) {
            $this->sendTemplate($key, $toEmail, $toName, $vars, $sensitiveKeys, $rawKeys);
        })->afterResponse();
    }

    /**
     * Renders and sends one of the editable templates.
     *
     * @param  array<string, string|null>  $vars
     * @param  array<int, string>  $sensitiveKeys  Any key here is replaced
     *     with a placeholder before the body is written to email_logs — the
     *     actual email sent to the recipient is never touched by this. Used
     *     for one-time secrets (a password-reset link) that have no reason
     *     to sit in the delivery log the way an order confirmation does.
     * @param  array<int, string>  $rawKeys  Any key here is substituted into
     *     the body as-is, not HTML-escaped. Every var is plain text by
     *     default — this is only for a var that is itself already-safe,
     *     deliberately-built HTML (an order's <ul> of line items), never for
     *     anything that came from a customer-supplied field.
     */
    public function sendTemplate(string $key, string $toEmail, ?string $toName, array $vars, array $sensitiveKeys = [], array $rawKeys = []): bool
    {
        if (! array_key_exists($key, self::TEMPLATES)) {
            return false;
        }

        $vars = array_merge($this->baseVars(), $vars);

        $loggedVars = $vars;
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (array_key_exists($sensitiveKey, $loggedVars)) {
                $loggedVars[$sensitiveKey] = '[redacted]';
            }
        }

        return $this->send(
            $toEmail,
            $toName,
            // The subject line is plain text, not HTML — escaping it would
            // put literal &amp; where a customer's inbox should show &.
            $this->replace($this->templateSubject($key), $vars),
            $this->replace($this->templateBody($key), $vars, $rawKeys, escape: true),
            $key,
            $sensitiveKeys ? $this->replace($this->templateBody($key), $loggedVars, $rawKeys, escape: true) : null,
        );
    }

    /**
     * Sends an already-rendered message and records the attempt either way.
     *
     * @param  string|null  $loggedBody  What to write to email_logs.body
     *     instead of $body, when the two must differ (a redacted secret).
     *     Never affects what is actually sent — that is always $body.
     */
    public function send(string $toEmail, ?string $toName, string $subject, string $body, string $type = 'manual', ?string $loggedBody = null): bool
    {
        $html = View::make('emails.layout', [
            'subject' => $subject,
            'body' => $body,
        ])->render();

        $log = [
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body' => $loggedBody ?? $body,
            'type' => $type,
        ];

        try {
            Mail::html($html, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName ?: null)->subject($subject);
            });

            EmailLog::create($log + ['status' => 'sent']);

            return true;
        } catch (Throwable $e) {
            EmailLog::create($log + ['status' => 'failed', 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param  array<string, string|null>  $vars
     * @param  array<int, string>  $rawKeys  Keys exempt from $escape — see
     *     sendTemplate().
     * @param  bool  $escape  HTML-escape every value not in $rawKeys before
     *     substituting it. Off by default: a subject line is plain text, not
     *     HTML, and escaping it would be wrong, not just unnecessary — only
     *     an HTML body context should ever pass true.
     */
    public function replace(string $text, array $vars, array $rawKeys = [], bool $escape = false): string
    {
        foreach ($vars as $key => $value) {
            $replacement = (string) $value;

            if ($escape && ! in_array($key, $rawKeys, true)) {
                $replacement = e($replacement);
            }

            $text = str_replace('{{'.$key.'}}', $replacement, $text);
        }

        // Anything the caller did not supply is dropped rather than left raw.
        return preg_replace('/\{\{\s*[a-z0-9_]+\s*\}\}/i', '', $text) ?? $text;
    }

    /** @return array<string, string> */
    public function baseVars(): array
    {
        return [
            'site_name' => (string) config('site.name'),
            'site_phone' => (string) config('site.phone'),
            'site_email' => (string) config('site.email'),
            'site_address' => (string) config('site.address'),
            'currency' => (string) config('site.currency'),
        ];
    }

    /**
     * True once SMTP has been configured — used to avoid queuing mail into a
     * void and to warn the admin on the dashboard.
     */
    public function configured(): bool
    {
        return (bool) $this->settings->get('mail_host');
    }
}
