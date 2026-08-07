<?php

namespace Tests\Feature\Admin;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * menu_items.menu_category_id is cascadeOnDelete() — deleting a category
 * removes its dishes via a raw DB foreign key cascade, bypassing Eloquent
 * entirely, so MenuItemController::destroy()'s image cleanup never runs for
 * them. See the full-project audit ("orphaned images on category
 * cascade-delete").
 */
class CategoryCascadeImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (['category-cleanup-one.jpg', 'category-cleanup-two.jpg'] as $name) {
            @unlink(public_path('uploads/menu/'.$name));
        }

        parent::tearDown();
    }

    public function test_deleting_a_category_removes_its_dishes_uploaded_images_from_disk(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $category = MenuCategory::create(['name' => 'Grills', 'slug' => 'grills', 'is_active' => true, 'sort_order' => 0]);

        $directory = public_path('uploads/menu');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pathOne = 'uploads/menu/category-cleanup-one.jpg';
        $pathTwo = 'uploads/menu/category-cleanup-two.jpg';
        file_put_contents(public_path($pathOne), 'fake-image-one');
        file_put_contents(public_path($pathTwo), 'fake-image-two');

        MenuItem::create([
            'menu_category_id' => $category->id, 'name' => 'Kebab', 'slug' => 'kebab',
            'price' => 25, 'image' => $pathOne, 'sort_order' => 0,
        ]);
        MenuItem::create([
            'menu_category_id' => $category->id, 'name' => 'Shawarma', 'slug' => 'shawarma',
            'price' => 20, 'image' => $pathTwo, 'sort_order' => 1,
        ]);

        $this->assertFileExists(public_path($pathOne));
        $this->assertFileExists(public_path($pathTwo));

        $response = $this->actingAs($admin, 'web')->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertFileDoesNotExist(public_path($pathOne));
        $this->assertFileDoesNotExist(public_path($pathTwo));
        $this->assertDatabaseMissing('menu_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('menu_items', ['menu_category_id' => $category->id]);
    }

    public function test_a_categorys_dishes_with_no_uploaded_image_do_not_cause_an_error_on_delete(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $category = MenuCategory::create(['name' => 'Drinks', 'slug' => 'drinks', 'is_active' => true, 'sort_order' => 0]);

        MenuItem::create([
            'menu_category_id' => $category->id, 'name' => 'Water', 'slug' => 'water',
            'price' => 2, 'image' => null, 'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin, 'web')->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('menu_categories', ['id' => $category->id]);
    }
}
