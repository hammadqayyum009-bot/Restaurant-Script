<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $img = fn (string $id, int $w = 800) => "https://images.unsplash.com/{$id}?auto=format&fit=crop&w={$w}&q=80";

        $categories = [
            [
                'name' => 'Mezze & Starters',
                'icon' => '🥗',
                'items' => [
                    ['Classic Hummus', 'Creamy chickpea dip with tahini, olive oil and a touch of paprika, served with warm Arabic bread.', 18, $img('photo-1541518763669-27fef04b14ea'), 'Levant', 0, false],
                    ['Mutabbal (Baba Ghanoush)', 'Smoky grilled eggplant blended with tahini, garlic and fresh lemon juice.', 19, $img('photo-1541518763669-27fef04b14ea'), 'Levant', 1, false],
                    ['Fattoush Salad', 'Crisp mixed greens, radish and toasted bread crisps tossed in sumac dressing.', 24, $img('photo-1540420773420-3366772f4999'), 'Levant', 0, false],
                    ['Sambousek Meat Pastries', 'Crispy golden pastries stuffed with spiced minced beef and onions.', 22, $img('photo-1490645935967-10de6ba17061'), 'UAE', 1, false],
                    ['Vine Leaves (Warak Enab)', 'Rice-stuffed grape leaves gently simmered with lemon and olive oil.', 20, $img('photo-1540420773420-3366772f4999'), 'Levant', 0, false],
                    ['Arabic Mixed Mezze Platter', 'A generous sharing platter of hummus, mutabbal, tabbouleh and marinated olives.', 45, $img('photo-1541518763669-27fef04b14ea'), 'Gulf', 0, true],
                ],
            ],
            [
                'name' => 'Charcoal Grills (Mashawi)',
                'icon' => '🍢',
                'items' => [
                    ['Shish Tawook', 'Char-grilled marinated chicken skewers served with garlic sauce and pickles.', 38, $img('photo-1512621776951-a57141f2eefd'), 'UAE', 1, true],
                    ['Lamb Chops Mixed Grill', 'Tender lamb chops grilled over charcoal, served with rice and fresh salad.', 68, $img('photo-1607330289024-1535c6b4e1c1'), 'Saudi Arabia', 1, true],
                    ['Kofta Kebab', 'Spiced minced lamb skewers grilled to perfection over an open flame.', 34, $img('photo-1576402187878-974f70c890a5'), 'Gulf', 2, false],
                    ['Chicken Tikka Skewers', 'Yogurt-marinated chicken cubes seasoned with authentic Gulf spices.', 36, $img('photo-1512621776951-a57141f2eefd'), 'Qatar', 2, false],
                    ['Arabic Mixed Grill Platter', 'A hearty combination of shish tawook, kofta and lamb chops with saffron rice.', 89, $img('photo-1607330289024-1535c6b4e1c1'), 'Gulf', 1, true],
                    ['Grilled Hammour Fish', 'Fresh Gulf hammour fish grilled with lemon, garlic and Arabian spices.', 72, $img('photo-1576402187878-974f70c890a5'), 'UAE', 1, false],
                ],
            ],
            [
                'name' => 'Rice & Mandi Specialities',
                'icon' => '🍚',
                'items' => [
                    ['Chicken Mandi', 'Slow-cooked chicken over fragrant basmati rice with traditional Yemeni spices.', 42, $img('photo-1585937421612-70a008356fbe'), 'Yemen', 1, true],
                    ['Lamb Mandi', 'Tender slow-roasted lamb served over aromatic spiced basmati rice.', 58, $img('photo-1585937421612-70a008356fbe'), 'Yemen', 1, true],
                    ['Chicken Machboos', 'Traditional Emirati spiced rice cooked with chicken and dried lime.', 40, $img('photo-1600891964092-4316c288032e'), 'UAE', 2, false],
                    ['Mutton Kabsa', 'Saudi-style spiced rice with slow-cooked mutton, nuts and raisins.', 55, $img('photo-1555939594-58d7cb561ad1'), 'Saudi Arabia', 2, false],
                    ['Harees', 'A traditional Gulf classic of slow-cooked wheat and tender meat, whipped smooth.', 30, $img('photo-1600891964092-4316c288032e'), 'Oman', 0, false],
                    ['Majboos Chicken', 'Bahraini-style spiced rice with chicken, tomato and caramelised onions.', 38, $img('photo-1555939594-58d7cb561ad1'), 'Bahrain', 1, false],
                ],
            ],
            [
                'name' => 'Shawarma & Sandwiches',
                'icon' => '🌯',
                'items' => [
                    ['Chicken Shawarma Wrap', 'Marinated chicken shawarma wrapped with garlic sauce, pickles and fries.', 16, $img('photo-1544025162-d76694265947'), 'Levant', 0, true],
                    ['Beef Shawarma Plate', 'Sliced beef shawarma served with rice, fresh salad and hummus.', 32, $img('photo-1544025162-d76694265947'), 'Levant', 0, false],
                    ['Falafel Wrap', 'Crispy golden falafel with tahini sauce, pickles and fresh vegetables.', 14, $img('photo-1540420773420-3366772f4999'), 'Levant', 0, false],
                    ['Arayes', 'Grilled Arabic bread stuffed and toasted with spiced minced meat.', 26, $img('photo-1490645935967-10de6ba17061'), 'Levant', 1, false],
                    ['Chicken Shawarma Plate', 'Juicy chicken shawarma served with rice, garlic sauce and salad.', 30, $img('photo-1544025162-d76694265947'), 'UAE', 0, false],
                ],
            ],
            [
                'name' => 'Manakish & Arabic Bread',
                'icon' => '🫓',
                'items' => [
                    ['Zaatar Manakish', 'Traditional flatbread topped with fragrant zaatar and extra virgin olive oil.', 12, $img('photo-1490645935967-10de6ba17061'), 'Levant', 0, false],
                    ['Cheese Manakish', 'Warm flatbread topped with melted Akkawi cheese, baked fresh.', 15, $img('photo-1490645935967-10de6ba17061'), 'Levant', 0, false],
                    ['Lahm Bi Ajeen', 'Arabic meat pie topped with spiced minced lamb, tomato and pepper.', 18, $img('photo-1490645935967-10de6ba17061'), 'Levant', 1, false],
                    ['Cheese & Zaatar Manakish', 'The best of both worlds — melted cheese and zaatar on one flatbread.', 17, $img('photo-1490645935967-10de6ba17061'), 'Levant', 0, false],
                ],
            ],
            [
                'name' => 'Desserts',
                'icon' => '🍮',
                'items' => [
                    ['Umm Ali', 'Warm Egyptian bread pudding baked with milk, nuts and golden raisins.', 22, $img('photo-1571091718767-18b5b1457add'), 'Egypt', 0, true],
                    ['Luqaimat', 'Crispy sweet dumplings drizzled with date syrup and sesame seeds.', 18, $img('photo-1571091718767-18b5b1457add'), 'UAE', 0, false],
                    ['Kunafa', 'Crispy shredded pastry layered with sweet cheese and fragrant syrup.', 26, $img('photo-1517248135467-4c7edcad34c4'), 'Levant', 0, true],
                    ['Baklava Assortment', 'Delicate layered filo pastry with crushed nuts and honey syrup.', 20, $img('photo-1571091718767-18b5b1457add'), 'Levant', 0, false],
                    ['Saffron Rice Pudding', 'Creamy rice pudding infused with saffron and rose water (Muhallabia).', 16, $img('photo-1517248135467-4c7edcad34c4'), 'Gulf', 0, false],
                ],
            ],
            [
                'name' => 'Hot & Cold Beverages',
                'icon' => '☕',
                'items' => [
                    ['Karak Chai', 'Spiced milk tea slow-brewed with cardamom — a Gulf favourite.', 8, $img('photo-1533089860892-a7c6f0a88666'), 'Gulf', 0, true],
                    ['Arabic Qahwa', 'Traditional Arabic coffee infused with cardamom, served with fresh dates.', 10, $img('photo-1571115177098-24ec42ed204d'), 'Gulf', 0, false],
                    ['Fresh Mint Lemonade', 'Refreshing lemonade blended with fresh mint leaves and crushed ice.', 14, $img('photo-1533089860892-a7c6f0a88666'), 'Levant', 0, false],
                    ['Jallab', 'Traditional date and rose syrup drink topped with pine nuts.', 12, $img('photo-1533089860892-a7c6f0a88666'), 'Levant', 0, false],
                    ['Laban (Buttermilk)', 'Chilled traditional yogurt drink, light and refreshing.', 9, $img('photo-1533089860892-a7c6f0a88666'), 'Gulf', 0, false],
                    ['Turkish Coffee', 'Rich, aromatic coffee brewed and served the traditional way.', 11, $img('photo-1544787219-7f47ccb76574'), 'Turkey', 0, false],
                ],
            ],
        ];

        foreach ($categories as $catIndex => $cat) {
            $category = MenuCategory::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'sort_order' => $catIndex,
                    'is_active' => true,
                ]
            );

            foreach ($cat['items'] as $itemIndex => $item) {
                [$name, $description, $price, $image, $origin, $spice, $featured] = $item;

                MenuItem::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'menu_category_id' => $category->id,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'image' => $image,
                        'origin' => $origin,
                        'spice_level' => $spice,
                        'is_featured' => $featured,
                        'is_available' => true,
                        'sort_order' => $itemIndex,
                    ]
                );
            }
        }
    }
}
