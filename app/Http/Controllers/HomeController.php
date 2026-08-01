<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;

class HomeController extends Controller
{
    public function index()
    {
        $featured = MenuItem::with('category')
            ->where('is_available', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        $categories = MenuCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('home', compact('featured', 'categories'));
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }
}
