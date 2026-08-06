<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Only takes effect if the host's cron actually calls "php artisan
// schedule:run" every minute — not guaranteed on shared cPanel hosting
// (see documentation/payments-buyer-guide.md), which is why the buyer guide
// also documents pointing cron directly at "php artisan payments:reconcile"
// as the simpler, more reliable alternative on hosts without that
// guarantee. withoutOverlapping() is scheduler-level protection; the
// command's own PaymentReconciliationService::run() lock is what actually
// makes concurrent runs safe regardless of how the command gets triggered.
Schedule::command('payments:reconcile')->everyFifteenMinutes()->withoutOverlapping();

// Same reasoning as above, faster cadence: a bulk email send has nothing to
// wait out (unlike a stuck payment, which needs time to genuinely resolve
// itself), so draining the queue as fast as safely possible is strictly
// better UX. BulkEmailSender::run()'s own lock makes concurrent runs safe
// regardless of how the command gets triggered, same as reconciliation.
Schedule::command('email:send-bulk-batch')->everyMinute()->withoutOverlapping();
