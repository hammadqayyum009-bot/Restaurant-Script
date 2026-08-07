<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Services\ActivityLogger;
use App\Services\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    public function __construct(protected Uploader $uploader, protected ActivityLogger $activity)
    {
    }

    public function index(Request $request)
    {
        $query = MenuItem::with('category');

        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category = $request->query('category')) {
            $query->where('menu_category_id', $category);
        }

        return view('admin.dishes.index', [
            'dishes' => $query->orderBy('menu_category_id')->orderBy('sort_order')->paginate(20)->withQueryString(),
            'categories' => MenuCategory::orderBy('sort_order')->get(),
            'search' => $search,
            'activeCategory' => $category,
        ]);
    }

    public function create()
    {
        return view('admin.dishes.form', [
            'dish' => new MenuItem(['is_available' => true, 'spice_level' => 0, 'sort_order' => 0]),
            'categories' => MenuCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $dish = MenuItem::create($data);

        $this->handleImage($request, $dish);
        $this->activity->created($dish, 'dish "'.$dish->name.'"');

        return redirect()->route('admin.dishes.index')->with('success', 'Dish created.');
    }

    public function edit(MenuItem $dish)
    {
        return view('admin.dishes.form', [
            'dish' => $dish,
            'categories' => MenuCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, MenuItem $dish)
    {
        $dish->update($this->validated($request, $dish));
        $this->handleImage($request, $dish);
        $this->activity->updated($dish, 'dish "'.$dish->name.'"');

        return redirect()->route('admin.dishes.index')->with('success', 'Dish updated.');
    }

    public function destroy(MenuItem $dish)
    {
        $this->uploader->delete($dish->image);
        $this->activity->deleted('dish "'.$dish->name.'"', $dish);
        $dish->delete();

        return redirect()->route('admin.dishes.index')->with('success', 'Dish deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?MenuItem $dish = null): array
    {
        $data = $request->validate([
            'menu_category_id' => ['required', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', Rule::unique('menu_items', 'slug')->ignore($dish?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'origin' => ['nullable', 'string', 'max:60'],
            'spice_level' => ['required', 'integer', 'min:0', 'max:3'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_available'] = $request->boolean('is_available');

        unset($data['image'], $data['image_url']);

        return $data;
    }

    /**
     * An uploaded file wins; otherwise an explicitly typed URL is used, which
     * keeps the seeded stock photography editable without re-uploading.
     */
    protected function handleImage(Request $request, MenuItem $dish): void
    {
        if ($request->hasFile('image')) {
            $old = $dish->image;
            $dish->image = $this->uploader->store($request->file('image'), 'menu');
            $dish->save();
            $this->uploader->delete($old);

            return;
        }

        $url = trim((string) $request->input('image_url'));

        if ($url !== '' && $url !== $dish->image) {
            $this->uploader->delete($dish->image);
            $dish->image = $url;
            $dish->save();
        }
    }
}
