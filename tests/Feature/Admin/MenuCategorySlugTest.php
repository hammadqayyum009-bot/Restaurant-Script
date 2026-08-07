<?php

namespace Tests\Feature\Admin;

use App\Models\MenuCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: MenuItemController already appends a random suffix to a
 * blank auto-derived slug so two dishes with the same name never collide.
 * MenuCategoryController never got the same treatment. See the
 * full-project audit ("blank-slug duplicate-name collision").
 */
class MenuCategorySlugTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    public function test_two_categories_with_the_same_name_and_a_blank_slug_do_not_collide(): void
    {
        $admin = $this->admin();

        $first = $this->actingAs($admin, 'web')->post(route('admin.categories.store'), [
            'name' => 'Specials', 'is_active' => '1',
        ]);
        $second = $this->actingAs($admin, 'web')->post(route('admin.categories.store'), [
            'name' => 'Specials', 'is_active' => '1',
        ]);

        $first->assertSessionHasNoErrors();
        $second->assertSessionHasNoErrors();

        $this->assertSame(2, MenuCategory::where('name', 'Specials')->count());
        $slugs = MenuCategory::where('name', 'Specials')->pluck('slug');
        $this->assertNotSame($slugs[0], $slugs[1]);
    }

    public function test_an_explicitly_typed_slug_is_still_used_as_is(): void
    {
        $response = $this->actingAs($this->admin(), 'web')->post(route('admin.categories.store'), [
            'name' => 'Mains', 'slug' => 'my-custom-slug', 'is_active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('my-custom-slug', MenuCategory::where('name', 'Mains')->value('slug'));
    }
}
