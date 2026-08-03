<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\Billing\BillingSettingsController;
use App\Http\Controllers\Admin\Billing\DocumentController;
use App\Http\Controllers\Admin\Billing\DocumentPrintController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\MenuCategoryController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    });

    Route::middleware('admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // ---- Menu ----
        Route::resource('categories', MenuCategoryController::class)->except('show');
        Route::resource('dishes', MenuItemController::class)->except('show');

        // ---- Content ----
        Route::resource('pages', PageController::class)->except('show');
        Route::get('/content/header', [ContentController::class, 'header'])->name('content.header');
        Route::put('/content/header', [ContentController::class, 'saveHeader'])->name('content.header.save');
        Route::get('/content/home', [ContentController::class, 'home'])->name('content.home');
        Route::put('/content/home', [ContentController::class, 'saveHome'])->name('content.home.save');
        Route::get('/content/footer', [ContentController::class, 'footer'])->name('content.footer');
        Route::put('/content/footer', [ContentController::class, 'saveFooter'])->name('content.footer.save');

        // ---- Operations ----
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
        Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::put('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('reservations.status');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::put('/messages/{message}/read', [MessageController::class, 'toggleRead'])->name('messages.read');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::put('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::resource('users', UserController::class)->except('show');

        // ---- Billing & documents ----
        Route::middleware('can:manage-billing')->group(function () {
            Route::get('/orders/{order}/billing/create', [DocumentController::class, 'createFromOrder'])->name('billing.from-order.create');
            Route::post('/orders/{order}/billing', [DocumentController::class, 'storeFromOrder'])->name('billing.from-order.store');

            Route::get('/billing/documents', [DocumentController::class, 'index'])->name('billing.index');
            // Static segments (create) must be declared before the {document}
            // wildcard, or Laravel would try to model-bind "create" as an id.
            Route::get('/billing/documents/create', [DocumentController::class, 'create'])->name('billing.create');
            Route::post('/billing/documents', [DocumentController::class, 'store'])->name('billing.store');
            Route::get('/billing/documents/{document}/edit', [DocumentController::class, 'edit'])->name('billing.edit');
            Route::get('/billing/documents/{document}', [DocumentController::class, 'show'])->name('billing.show');
            Route::put('/billing/documents/{document}', [DocumentController::class, 'update'])->name('billing.update');
            Route::delete('/billing/documents/{document}', [DocumentController::class, 'destroy'])->name('billing.destroy');
            Route::post('/billing/documents/{document}/issue', [DocumentController::class, 'issue'])->name('billing.issue');
            Route::post('/billing/documents/{document}/archive', [DocumentController::class, 'archive'])->name('billing.archive');
            Route::get('/billing/documents/{document}/print', [DocumentPrintController::class, 'show'])->name('billing.print');
        });

        // ---- Reporting ----
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
        Route::get('/export/orders', [ExportController::class, 'orders'])->name('export.orders');
        Route::get('/export/reservations', [ExportController::class, 'reservations'])->name('export.reservations');
        Route::get('/export/customers', [ExportController::class, 'customers'])->name('export.customers');

        // ---- Settings ----
        Route::get('/settings/site', [SettingsController::class, 'site'])->name('settings.site');
        Route::put('/settings/site', [SettingsController::class, 'saveSite'])->name('settings.site.save');
        Route::get('/settings/panel', [SettingsController::class, 'panel'])->name('settings.panel');
        Route::put('/settings/panel', [SettingsController::class, 'savePanel'])->name('settings.panel.save');
        Route::get('/settings/seo', [SettingsController::class, 'seo'])->name('settings.seo');
        Route::put('/settings/seo', [SettingsController::class, 'saveSeo'])->name('settings.seo.save');
        Route::get('/settings/shop', [SettingsController::class, 'shop'])->name('settings.shop');
        Route::put('/settings/shop', [SettingsController::class, 'saveShop'])->name('settings.shop.save');

        // ---- Billing & documents (Batch 1: settings only) ----
        Route::middleware('can:manage-billing')->group(function () {
            Route::get('/settings/billing', [BillingSettingsController::class, 'edit'])->name('settings.billing');
            Route::put('/settings/billing', [BillingSettingsController::class, 'update'])->name('settings.billing.save');
        });

        // ---- Email ----
        Route::get('/email/smtp', [MailController::class, 'smtp'])->name('email.smtp');
        Route::put('/email/smtp', [MailController::class, 'saveSmtp'])->name('email.smtp.save');
        Route::post('/email/test', [MailController::class, 'sendTest'])->name('email.test');
        Route::get('/email/templates', [MailController::class, 'templates'])->name('email.templates');
        Route::put('/email/templates', [MailController::class, 'saveTemplates'])->name('email.templates.save');
        Route::get('/email/compose', [MailController::class, 'compose'])->name('email.compose');
        Route::post('/email/compose', [MailController::class, 'sendCompose'])->name('email.compose.send');
        Route::get('/email/logs', [MailController::class, 'logs'])->name('email.logs');
    });
});
