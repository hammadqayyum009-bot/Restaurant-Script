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
        'admin_reservation' => [
            'label' => 'New reservation alert (admin copy)',
            'subject' => 'New table request — {{date}} {{time}}',
            'body' => "<p>{{name}} ({{phone}}) requested a table for <strong>{{guests}}</strong> on <strong>{{date}}</strong> at <strong>{{time}}</strong>.</p>\n<p>Notes: {{notes}}</p>",
            'vars' => ['name', 'phone', 'guests', 'date', 'time', 'notes'],
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
     * Renders and sends one of the editable templates.
     *
     * @param  array<string, string|null>  $vars
     */
    public function sendTemplate(string $key, string $toEmail, ?string $toName, array $vars): bool
    {
        if (! array_key_exists($key, self::TEMPLATES)) {
            return false;
        }

        $vars = array_merge($this->baseVars(), $vars);

        return $this->send(
            $toEmail,
            $toName,
            $this->replace($this->templateSubject($key), $vars),
            $this->replace($this->templateBody($key), $vars),
            $key
        );
    }

    /**
     * Sends an already-rendered message and records the attempt either way.
     */
    public function send(string $toEmail, ?string $toName, string $subject, string $body, string $type = 'manual'): bool
    {
        $html = View::make('emails.layout', [
            'subject' => $subject,
            'body' => $body,
        ])->render();

        $log = [
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body' => $body,
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
     */
    public function replace(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
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
