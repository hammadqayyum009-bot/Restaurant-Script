<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Payments\PaymentReconciliationService;
use App\Services\Mailer;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Mailer $mailer, PaymentReconciliationService $reconciliation)
    {
        $today = now()->startOfDay();

        return view('admin.dashboard', [
            'stuckPaymentsCount' => Gate::allows('manage-payments') ? $reconciliation->stuckCount() : 0,
            'ordersTotal' => Order::count(),
            'ordersToday' => Order::where('created_at', '>=', $today)->count(),
            'ordersPending' => Order::where('status', 'pending')->count(),
            'revenueTotal' => (float) Order::whereNotIn('status', ['cancelled'])->sum('total'),
            'revenueToday' => (float) Order::whereNotIn('status', ['cancelled'])
                ->where('created_at', '>=', $today)->sum('total'),
            'reservationsPending' => Reservation::where('status', 'pending')->count(),
            'reservationsUpcoming' => Reservation::whereDate('reservation_date', '>=', $today)
                ->whereIn('status', ['pending', 'confirmed'])->count(),
            'reviewsPending' => Review::where('is_approved', false)->count(),
            'messagesUnread' => ContactMessage::where('is_read', false)->count(),
            'usersTotal' => User::where('is_admin', false)->count(),
            'dishesTotal' => MenuItem::count(),
            'dishesUnavailable' => MenuItem::where('is_available', false)->count(),
            'recentOrders' => Order::latest()->limit(8)->get(),
            'recentReservations' => Reservation::latest()->limit(6)->get(),
            'mailConfigured' => $mailer->configured(),
        ]);
    }
}
