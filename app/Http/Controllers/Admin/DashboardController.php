<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use App\Services\Mailer;

class DashboardController extends Controller
{
    public function index(Mailer $mailer)
    {
        $today = now()->startOfDay();

        return view('admin.dashboard', [
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
            'usersTotal' => User::where('is_admin', false)->count(),
            'dishesTotal' => MenuItem::count(),
            'dishesUnavailable' => MenuItem::where('is_available', false)->count(),
            'recentOrders' => Order::latest()->limit(8)->get(),
            'recentReservations' => Reservation::latest()->limit(6)->get(),
            'mailConfigured' => $mailer->configured(),
        ]);
    }
}
