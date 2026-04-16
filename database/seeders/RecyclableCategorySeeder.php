<?php

namespace Database\Seeders;

use App\Models\RecyclableCategory;
use Illuminate\Database\Seeder;

class RecyclableCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Plastic',
                'slug' => 'plastic',
                'description' => 'All types of recyclable plastic materials',
                'icon' => 'plastic-bottle',
                'unit' => 'kg',
                'price_per_unit' => 0.50,
                'min_quantity' => 0.5,
                'sort_order' => 1,
                'children' => [
                    ['name' => 'PET Bottles', 'slug' => 'pet-bottles', 'price_per_unit' => 0.60, 'unit' => 'kg'],
                    ['name' => 'HDPE Containers', 'slug' => 'hdpe-containers', 'price_per_unit' => 0.45, 'unit' => 'kg'],
                    ['name' => 'Plastic Bags', 'slug' => 'plastic-bags', 'price_per_unit' => 0.20, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Paper',
                'slug' => 'paper',
                'description' => 'Newspapers, magazines, cardboard, and other paper products',
                'icon' => 'newspaper',
                'unit' => 'kg',
                'price_per_unit' => 0.30,
                'min_quantity' => 1.0,
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Newspaper', 'slug' => 'newspaper', 'price_per_unit' => 0.25, 'unit' => 'kg'],
                    ['name' => 'Cardboard', 'slug' => 'cardboard', 'price_per_unit' => 0.35, 'unit' => 'kg'],
                    ['name' => 'Mixed Paper', 'slug' => 'mixed-paper', 'price_per_unit' => 0.20, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Metal',
                'slug' => 'metal',
                'description' => 'Aluminum cans, steel, copper, and other metals',
                'icon' => 'metal-can',
                'unit' => 'kg',
                'price_per_unit' => 2.00,
                'min_quantity' => 0.5,
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Aluminum Cans', 'slug' => 'aluminum-cans', 'price_per_unit' => 3.50, 'unit' => 'kg'],
                    ['name' => 'Steel/Iron', 'slug' => 'steel-iron', 'price_per_unit' => 1.50, 'unit' => 'kg'],
                    ['name' => 'Copper', 'slug' => 'copper', 'price_per_unit' => 15.00, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Glass',
                'slug' => 'glass',
                'description' => 'Glass bottles, jars, and other glass materials',
                'icon' => 'glass-bottle',
                'unit' => 'kg',
                'price_per_unit' => 0.15,
                'min_quantity' => 1.0,
                'sort_order' => 4,
            ],
            [
                'name' => 'E-Waste',
                'slug' => 'e-waste',
                'description' => 'Electronic waste including phones, computers, batteries',
                'icon' => 'circuit-board',
                'unit' => 'unit',
                'price_per_unit' => 5.00,
                'min_quantity' => 1,
                'sort_order' => 5,
                'children' => [
                    ['name' => 'Mobile Phones', 'slug' => 'mobile-phones', 'price_per_unit' => 10.00, 'unit' => 'unit'],
                    ['name' => 'Laptops/Computers', 'slug' => 'laptops-computers', 'price_per_unit' => 25.00, 'unit' => 'unit'],
                    ['name' => 'Batteries', 'slug' => 'batteries', 'price_per_unit' => 2.00, 'unit' => 'kg'],
                    ['name' => 'Cables & Wires', 'slug' => 'cables-wires', 'price_per_unit' => 3.00, 'unit' => 'kg'],
                ],
            ],
            [
                'name' => 'Textile',
                'slug' => 'textile',
                'description' => 'Used clothing, fabric scraps, and textile materials',
                'icon' => 'shirt',
                'unit' => 'kg',
                'price_per_unit' => 0.40,
                'min_quantity' => 1.0,
                'sort_order' => 6,
            ],
            [
                'name' => 'Used Cooking Oil',
                'slug' => 'used-cooking-oil',
                'description' => 'Used cooking oil for biodiesel production',
                'icon' => 'oil-can',
                'unit' => 'kg',
                'price_per_unit' => 1.00,
                'min_quantity' => 1.0,
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = RecyclableCategory::create($categoryData);

            foreach ($children as $childData) {
                $childData['parent_id'] = $parent->id;
                $childData['icon'] = $parent->icon;
                $childData['min_quantity'] = $childData['min_quantity'] ?? $parent->min_quantity;
                $childData['sort_order'] = $parent->sort_order;
                RecyclableCategory::create($childData);
            }
        }
    }
}
