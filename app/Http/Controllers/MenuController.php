<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public const SPICE_LEVELS = [0 => 'Not spicy', 1 => 'Mild', 2 => 'Medium', 3 => 'Hot'];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $spice = $request->query('spice');
        $maxPrice = $request->query('max_price');
        $activeCategory = $request->query('category');

        $filtering = $search !== '' || $spice !== null && $spice !== '' || ($maxPrice !== null && $maxPrice !== '');

        $categories = MenuCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->with(['availableItems' => function ($query) use ($search, $spice, $maxPrice) {
                if ($search !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('origin', 'like', "%{$search}%");
                    });
                }

                if ($spice !== null && $spice !== '') {
                    $query->where('spice_level', (int) $spice);
                }

                if ($maxPrice !== null && $maxPrice !== '') {
                    $query->where('price', '<=', (float) $maxPrice);
                }
            }])
            ->get();

        // While filtering, empty categories are noise — drop them.
        if ($filtering) {
            $categories = $categories->filter(fn ($category) => $category->availableItems->isNotEmpty())->values();
        }

        return view('menu', [
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'search' => $search,
            'spice' => $spice,
            'maxPrice' => $maxPrice,
            'filtering' => $filtering,
            'matchCount' => $categories->sum(fn ($category) => $category->availableItems->count()),
            'spiceLevels' => self::SPICE_LEVELS,
            'priceCeiling' => (float) (MenuItem::where('is_available', true)->max('price') ?? 0),
        ]);
    }
}
