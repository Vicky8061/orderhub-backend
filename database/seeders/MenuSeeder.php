<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Combo;
use App\Models\MenuItem;
use App\Models\Modifier;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Categories
        $burgers = Category::create(['name' => 'Burgers', 'display_order' => 1]);
        $sides = Category::create(['name' => 'Sides', 'display_order' => 2]);
        $drinks = Category::create(['name' => 'Drinks', 'display_order' => 3]);
        $desserts = Category::create(['name' => 'Desserts', 'display_order' => 4]);

        // 2. Modifiers
        $extraCheese = Modifier::create(['name' => 'Extra Cheese', 'price_adjustment' => 0.75]);
        $bacon = Modifier::create(['name' => 'Crispy Bacon', 'price_adjustment' => 1.50]);
        $noOnion = Modifier::create(['name' => 'No Onion', 'price_adjustment' => 0.00]);
        $jalapenos = Modifier::create(['name' => 'Jalapenos', 'price_adjustment' => 0.50]);
        $largeSize = Modifier::create(['name' => 'Upgrade to Large', 'price_adjustment' => 1.00]);

        // 3. Menu Items - Burgers
        $cheeseBurger = MenuItem::create([
            'category_id' => $burgers->id,
            'name' => 'Classic Cheeseburger',
            'description' => 'Juicy beef patty, melted cheddar, lettuce, tomato, and house special sauce.',
            'price' => 6.99,
            'is_available' => true,
        ]);
        $cheeseBurger->modifiers()->attach([$extraCheese->id, $bacon->id, $noOnion->id, $jalapenos->id]);

        $smashBurger = MenuItem::create([
            'category_id' => $burgers->id,
            'name' => 'Double Smash Burger',
            'description' => 'Two smashed beef patties, caramelized onions, double American cheese on brioche bun.',
            'price' => 8.99,
            'is_available' => true,
        ]);
        $smashBurger->modifiers()->attach([$extraCheese->id, $bacon->id, $jalapenos->id]);

        $chickenBurger = MenuItem::create([
            'category_id' => $burgers->id,
            'name' => 'Crispy Chicken Sandwich',
            'description' => 'Golden fried chicken breast, tangy pickles, spicy mayo on sesame seed bun.',
            'price' => 7.49,
            'is_available' => true,
        ]);
        $chickenBurger->modifiers()->attach([$extraCheese->id, $bacon->id, $jalapenos->id]);

        // 4. Menu Items - Sides
        $fries = MenuItem::create([
            'category_id' => $sides->id,
            'name' => 'Golden French Fries',
            'description' => 'Crispy sea-salted potato fries.',
            'price' => 2.99,
            'is_available' => true,
        ]);
        $fries->modifiers()->attach([$extraCheese->id, $largeSize->id]);

        $onionRings = MenuItem::create([
            'category_id' => $sides->id,
            'name' => 'Crispy Onion Rings',
            'description' => 'Battered sweet onion rings served with campfire mayo.',
            'price' => 3.99,
            'is_available' => true,
        ]);

        // 5. Menu Items - Drinks
        $coke = MenuItem::create([
            'category_id' => $drinks->id,
            'name' => 'Fountain Soda (Coke)',
            'description' => 'Chilled Coca-Cola fountain drink.',
            'price' => 1.99,
            'is_available' => true,
        ]);
        $coke->modifiers()->attach([$largeSize->id]);

        $shake = MenuItem::create([
            'category_id' => $drinks->id,
            'name' => 'Chocolate Milkshake',
            'description' => 'Rich creamy chocolate shake topped with whipped cream.',
            'price' => 4.49,
            'is_available' => true,
        ]);

        // 6. Combos
        $valueMeal = Combo::create([
            'name' => 'Cheeseburger Value Meal',
            'price' => 9.99,
            'is_available' => true,
        ]);
        $valueMeal->menuItems()->attach([
            $cheeseBurger->id => ['quantity' => 1],
            $fries->id => ['quantity' => 1],
            $coke->id => ['quantity' => 1],
        ]);
    }
}
