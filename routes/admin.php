<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\MenuCategoryController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProfileController;
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

        // ---- Settings ----
        Route::get('/settings/site', [SettingsController::class, 'site'])->name('settings.site');
        Route::put('/settings/site', [SettingsController::class, 'saveSite'])->name('settings.site.save');
        Route::get('/settings/panel', [SettingsController::class, 'panel'])->name('settings.panel');
        Route::put('/settings/panel', [SettingsController::class, 'savePanel'])->name('settings.panel.save');
        Route::get('/settings/shop', [SettingsController::class, 'shop'])->name('settings.shop');
        Route::put('/settings/shop', [SettingsController::class, 'saveShop'])->name('settings.shop.save');

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
