<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutPaymentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentResultController;
use App\Http\Controllers\Payments\MoyasarCallbackController;
use App\Http\Controllers\Payments\MoyasarWebhookController;
use App\Http\Controllers\Payments\TapCallbackController;
use App\Http\Controllers\Payments\TapWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'databaseForm'])->name('database');
    Route::post('/database', [InstallController::class, 'databaseStore'])->name('database.store');
    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
});

Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware('installed')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/about', [HomeController::class, 'about'])->name('about');
    Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{menuItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{menuItem}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::middleware('signed')->group(function () {
        Route::get('/checkout/{order}/payment', [CheckoutPaymentController::class, 'show'])->name('checkout.payment.show');
        Route::post('/checkout/{order}/payment', [CheckoutPaymentController::class, 'store'])
            ->name('checkout.payment.store')->middleware('throttle:10,1');
        Route::get('/checkout/{order}/result', [PaymentResultController::class, 'show'])->name('checkout.payment.result');
    });

    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/payments/moyasar/callback', [MoyasarCallbackController::class, 'handle'])->name('payments.moyasar.callback');
        Route::post('/payments/moyasar/webhook', [MoyasarWebhookController::class, 'handle'])->name('payments.moyasar.webhook');
        Route::get('/payments/tap/callback', [TapCallbackController::class, 'handle'])->name('payments.tap.callback');
        Route::post('/payments/tap/webhook', [TapWebhookController::class, 'handle'])->name('payments.tap.webhook');
    });

    Route::get('/track', [OrderTrackingController::class, 'show'])->name('track.show');
    Route::post('/track', [OrderTrackingController::class, 'find'])->name('track.find');

    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('page.show');

    Route::get('/reservations/book', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/success/{reservation}', [ReservationController::class, 'success'])
        ->name('reservations.success')->middleware('signed');

    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);

        Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::middleware(['auth', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    require __DIR__.'/admin.php';
});
