<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $categories = MenuCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->with(['availableItems'])
            ->get();

        $activeCategory = $request->query('category');

        return view('menu', compact('categories', 'activeCategory'));
    }
}
