<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuCategoryController extends Controller
{
    public function __construct(protected ActivityLogger $activity) {}

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
        $category = MenuCategory::create($this->validated($request));
        $this->activity->created($category, 'category "'.$category->name.'"');

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(MenuCategory $category)
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, MenuCategory $category)
    {
        $category->update($this->validated($request, $category));
        $this->activity->updated($category, 'category "'.$category->name.'"');

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(MenuCategory $category)
    {
        // Dishes cascade with the category, so say so plainly rather than
        // silently deleting a chunk of the menu.
        $dishes = $category->menuItems()->count();
        $this->activity->deleted('category "'.$category->name.'" and '.$dishes.' dishes', $category);
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

        // Random suffix on a blank auto-derived slug, same as
        // MenuItemController — without it, two categories with the same
        // name (or names that slugify identically) collide on the unique
        // index, and the admin gets a confusing "slug already taken" error
        // for a field they never typed into.
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
