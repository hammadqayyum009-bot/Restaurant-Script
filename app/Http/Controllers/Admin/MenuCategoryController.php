<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuCategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => MenuCategory::withCount('menuItems')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new MenuCategory(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        MenuCategory::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(MenuCategory $category)
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, MenuCategory $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(MenuCategory $category)
    {
        // Dishes cascade with the category, so say so plainly rather than
        // silently deleting a chunk of the menu.
        $dishes = $category->menuItems()->count();
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', $dishes > 0
                ? "Category deleted along with {$dishes} dishes."
                : 'Category deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?MenuCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('menu_categories', 'slug')->ignore($category?->id)],
            'icon' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
