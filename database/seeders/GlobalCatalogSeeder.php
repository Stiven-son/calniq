<?php

namespace Database\Seeders;

use App\Models\GlobalCategory;
use App\Models\GlobalService;
use Illuminate\Database\Seeder;

class GlobalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [

            // ──────────────────────────────────────────────
            // 1. UPHOLSTERY CLEANING
            // ──────────────────────────────────────────────
            [
                'name' => 'Upholstery Cleaning',
                'slug' => 'upholstery-cleaning',
                'description' => 'Fabric furniture deep steam cleaning',
                'is_active' => true,
                'services' => [
                    ['name' => 'Loveseat (2 seats)', 'description' => 'Deep Steam Cleaning', 'default_price' => 79.00, 'duration_minutes' => 30],
                    ['name' => 'Sofa (3 seats)', 'description' => 'Deep Steam Cleaning', 'default_price' => 99.00, 'duration_minutes' => 40],
                    ['name' => 'Sectional L-Shape', 'description' => 'Deep Steam Cleaning', 'default_price' => 149.00, 'duration_minutes' => 60],
                    ['name' => 'Sectional U-Shape', 'description' => 'Deep Steam Cleaning', 'default_price' => 199.00, 'duration_minutes' => 75],
                    ['name' => 'Sectional Symmetric', 'description' => 'Deep Steam Cleaning', 'default_price' => 179.00, 'duration_minutes' => 70],
                    ['name' => 'Armchair', 'description' => 'Deep Steam Cleaning', 'default_price' => 68.00, 'duration_minutes' => 20],
                    ['name' => 'Recliner', 'description' => 'Deep Steam Cleaning', 'default_price' => 75.00, 'duration_minutes' => 25],
                    ['name' => 'Ottoman', 'description' => 'Deep Steam Cleaning', 'default_price' => 40.00, 'duration_minutes' => 15],
                    ['name' => 'Dining Chair (Back + Seat)', 'description' => 'Deep Steam Cleaning', 'default_price' => 38.00, 'price_type' => 'per_unit', 'price_unit' => 'chair', 'duration_minutes' => 10],
                    ['name' => 'Dining Chair (Seat Only)', 'description' => 'Deep Steam Cleaning', 'default_price' => 28.00, 'price_type' => 'per_unit', 'price_unit' => 'chair', 'duration_minutes' => 7],
                    ['name' => 'Office Chair', 'description' => 'Deep Steam Cleaning', 'default_price' => 38.00, 'duration_minutes' => 15],
                    ['name' => 'Upholstered Bench', 'description' => 'Deep Steam Cleaning', 'default_price' => 40.00, 'duration_minutes' => 15],
                    ['name' => 'Additional Seat or Cushion', 'description' => 'Deep Steam Cleaning', 'default_price' => 35.00, 'price_type' => 'per_unit', 'price_unit' => 'seat', 'duration_minutes' => 10],
                    ['name' => 'Sleeper Sofa / Sofa Bed', 'description' => 'Deep Steam Cleaning', 'default_price' => 129.00, 'duration_minutes' => 50],
                ],
            ],

            // ──────────────────────────────────────────────
            // 2. LEATHER CLEANING
            // ──────────────────────────────────────────────
            [
                'name' => 'Leather Cleaning',
                'slug' => 'leather-cleaning',
                'description' => 'Leather furniture cleaning & conditioning',
                'is_active' => false,  // Waiting for icons
                'services' => [
                    ['name' => 'Leather Armchair', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 85.00, 'duration_minutes' => 25],
                    ['name' => 'Leather Recliner', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 100.00, 'duration_minutes' => 30],
                    ['name' => 'Leather Three Seater', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 195.00, 'duration_minutes' => 50],
                    ['name' => 'Leather Sectional (up to 4 Seats)', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 225.00, 'duration_minutes' => 60],
                    ['name' => 'Leather Sectional (5 Seats)', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 260.00, 'duration_minutes' => 70],
                    ['name' => 'Leather Sectional (6 Seats)', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 295.00, 'duration_minutes' => 80],
                    ['name' => 'Leather Sectional (7 Seats)', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 330.00, 'duration_minutes' => 90],
                    ['name' => 'Leather Chaise Lounge', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 120.00, 'duration_minutes' => 30],
                    ['name' => 'Leather Cushion', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 35.00, 'price_type' => 'per_unit', 'price_unit' => 'cushion', 'duration_minutes' => 10],
                    ['name' => 'Leather Dining Chair (Back + Seat)', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 50.00, 'price_type' => 'per_unit', 'price_unit' => 'chair', 'duration_minutes' => 12],
                    ['name' => 'Leather Bench', 'description' => 'Deep leather cleaning & conditioning', 'default_price' => 55.00, 'duration_minutes' => 20],
                ],
            ],

            // ──────────────────────────────────────────────
            // 3. MATTRESS CLEANING
            // ──────────────────────────────────────────────
            [
                'name' => 'Mattress Cleaning',
                'slug' => 'mattress-cleaning',
                'description' => 'Deep cleaning and sanitizing for mattresses',
                'is_active' => true,
                'services' => [
                    ['name' => 'Mattress Twin Size', 'description' => 'Deep Steam Cleaning', 'default_price' => 79.00, 'duration_minutes' => 30],
                    ['name' => 'Mattress Full Size', 'description' => 'Deep Steam Cleaning', 'default_price' => 99.00, 'duration_minutes' => 35],
                    ['name' => 'Mattress Queen Size', 'description' => 'Deep Steam Cleaning', 'default_price' => 119.00, 'duration_minutes' => 40],
                    ['name' => 'Mattress King Size', 'description' => 'Deep Steam Cleaning', 'default_price' => 150.00, 'duration_minutes' => 45],
                    ['name' => 'Bed Frame', 'description' => 'Deep Steam Cleaning', 'default_price' => 65.00, 'duration_minutes' => 20],
                    ['name' => 'Headboard', 'description' => 'Deep Steam Cleaning', 'default_price' => 65.00, 'duration_minutes' => 20],
                ],
            ],

            // ──────────────────────────────────────────────
            // 4. CARPET CLEANING
            // ──────────────────────────────────────────────
            [
                'name' => 'Carpet Cleaning',
                'slug' => 'carpet-cleaning',
                'description' => 'Deep steam carpet cleaning for residential rooms',
                'is_active' => true,
                'services' => [
                    ['name' => 'Room / Bedroom', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 95.00, 'price_type' => 'per_unit', 'price_unit' => 'room', 'duration_minutes' => 30],
                    ['name' => 'Hallway', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 45.00, 'duration_minutes' => 20],
                    ['name' => 'Walk-in Closet', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 28.00, 'duration_minutes' => 15],
                    ['name' => 'Staircase (up to 12 stairs)', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 108.00, 'duration_minutes' => 30],
                    ['name' => 'Individual Stair', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 9.00, 'price_type' => 'per_unit', 'price_unit' => 'stair', 'duration_minutes' => 3],
                    ['name' => 'Staircase Landing', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 25.00, 'duration_minutes' => 10],
                    ['name' => 'Basement (price per sq.ft.)', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 0.70, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 100, 'max_quantity' => 3000, 'duration_minutes' => 60],
                    ['name' => 'Open Area (price per sq.ft.)', 'description' => 'Deep Steam Carpet Cleaning', 'default_price' => 0.70, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 100, 'max_quantity' => 3000, 'duration_minutes' => 60],
                ],
            ],

            // ──────────────────────────────────────────────
            // 5. AREA RUG CLEANING
            // ──────────────────────────────────────────────
            [
                'name' => 'Area Rug Cleaning',
                'slug' => 'area-rug-cleaning',
                'description' => 'Professional area rug cleaning — at home or pick up & delivery',
                'is_active' => true,
                'services' => [
                    ['name' => 'Synthetic Rug — At Home', 'description' => 'Deep Steam Cleaning', 'default_price' => 1.75, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 10, 'max_quantity' => 500, 'duration_minutes' => 30],
                    ['name' => 'Natural Rug — At Home', 'description' => 'Deep Steam Cleaning', 'default_price' => 3.50, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 10, 'max_quantity' => 500, 'duration_minutes' => 40],
                    ['name' => 'Synthetic Rug — Pick Up & Delivery', 'description' => 'Deep Steam Cleaning + pick up & delivery', 'default_price' => 2.50, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 10, 'max_quantity' => 500, 'duration_minutes' => 30],
                    ['name' => 'Natural Rug — Pick Up & Delivery', 'description' => 'Deep Steam Cleaning + pick up & delivery', 'default_price' => 5.00, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 10, 'max_quantity' => 500, 'duration_minutes' => 40],
                ],
            ],

            // ──────────────────────────────────────────────
            // 6. DRAPERY & CURTAIN (inactive — waiting for icons)
            // ──────────────────────────────────────────────
            [
                'name' => 'Drapery & Curtain',
                'slug' => 'drapery-curtain',
                'description' => 'Curtain and drapery cleaning — on-site or take down & rehang',
                'is_active' => false,
                'services' => [
                    ['name' => 'Sheer Curtain Panel', 'description' => 'Steam cleaning, per panel', 'default_price' => 25.00, 'price_type' => 'per_unit', 'price_unit' => 'panel', 'duration_minutes' => 10],
                    ['name' => 'Lined Curtain Panel', 'description' => 'Steam cleaning, per panel', 'default_price' => 45.00, 'price_type' => 'per_unit', 'price_unit' => 'panel', 'duration_minutes' => 15],
                    ['name' => 'Heavy Drapery Panel', 'description' => 'Steam cleaning, per panel', 'default_price' => 65.00, 'price_type' => 'per_unit', 'price_unit' => 'panel', 'duration_minutes' => 20],
                    ['name' => 'Valance / Swag', 'description' => 'Steam cleaning', 'default_price' => 20.00, 'price_type' => 'per_unit', 'price_unit' => 'piece', 'duration_minutes' => 10],
                ],
            ],

            // ──────────────────────────────────────────────
            // 7. TILE & GROUT (inactive — waiting for icons)
            // ──────────────────────────────────────────────
            [
                'name' => 'Tile & Grout Cleaning',
                'slug' => 'tile-grout-cleaning',
                'description' => 'Professional tile and grout deep cleaning',
                'is_active' => false,
                'services' => [
                    ['name' => 'Kitchen Floor', 'description' => 'Tile & grout deep cleaning', 'default_price' => 149.00, 'duration_minutes' => 60],
                    ['name' => 'Bathroom Floor', 'description' => 'Tile & grout deep cleaning', 'default_price' => 99.00, 'duration_minutes' => 45],
                    ['name' => 'Shower / Tub Area', 'description' => 'Tile & grout cleaning for shower walls and tub surround', 'default_price' => 129.00, 'duration_minutes' => 60],
                    ['name' => 'Entryway / Foyer', 'description' => 'Tile & grout deep cleaning', 'default_price' => 89.00, 'duration_minutes' => 30],
                    ['name' => 'Custom Area (per sq.ft.)', 'description' => 'Tile & grout cleaning priced per sq ft', 'default_price' => 1.50, 'price_type' => 'per_sqft', 'price_unit' => 'sq. ft.', 'min_quantity' => 50, 'max_quantity' => 2000, 'duration_minutes' => 60],
                ],
            ],

            // ──────────────────────────────────────────────
            // 8-13: Placeholder categories (inactive, ready for expansion)
            // ──────────────────────────────────────────────
            [
                'name' => 'Hardwood Floor Cleaning',
                'slug' => 'hardwood-floor-cleaning',
                'description' => 'Professional hardwood floor cleaning and conditioning',
                'is_active' => false,
                'services' => [],
            ],
            [
                'name' => 'Move In/Out Cleaning',
                'slug' => 'move-in-out-cleaning',
                'description' => 'Complete move in/out deep cleaning',
                'is_active' => false,
                'services' => [],
            ],
            [
                'name' => 'Deep Cleaning',
                'slug' => 'deep-cleaning',
                'description' => 'Thorough deep cleaning — baseboards, inside cabinets, appliances',
                'is_active' => false,
                'services' => [],
            ],
            [
                'name' => 'Regular Cleaning',
                'slug' => 'regular-cleaning',
                'description' => 'Standard recurring cleaning — vacuuming, mopping, dusting',
                'is_active' => false,
                'services' => [],
            ],
            [
                'name' => 'Commercial Cleaning',
                'slug' => 'commercial-cleaning',
                'description' => 'Professional office and commercial space cleaning',
                'is_active' => false,
                'services' => [],
            ],
            [
                'name' => 'Pressure Washing',
                'slug' => 'pressure-washing',
                'description' => 'High-pressure cleaning for driveways, patios, exteriors',
                'is_active' => false,
                'services' => [],
            ],
        ];

        $categorySortOrder = 0;

        foreach ($catalog as $categoryData) {
            $category = GlobalCategory::create([
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'description' => $categoryData['description'],
                'sort_order' => $categorySortOrder++,
                'is_active' => $categoryData['is_active'],
            ]);

            $serviceSortOrder = 0;
            foreach ($categoryData['services'] as $serviceData) {
                GlobalService::create([
                    'global_category_id' => $category->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'] ?? null,
                    'default_price' => $serviceData['default_price'],
                    'price_type' => $serviceData['price_type'] ?? 'fixed',
                    'price_unit' => $serviceData['price_unit'] ?? null,
                    'min_quantity' => $serviceData['min_quantity'] ?? 1,
                    'max_quantity' => $serviceData['max_quantity'] ?? 10,
                    'duration_minutes' => $serviceData['duration_minutes'] ?? 60,
                    'sort_order' => $serviceSortOrder++,
                    'is_active' => true,
                ]);
            }
        }

        $totalServices = GlobalService::count();
        $activeCategories = GlobalCategory::where('is_active', true)->count();
        $totalCategories = GlobalCategory::count();

        $this->command->info("✅ Created {$totalCategories} categories ({$activeCategories} active) with {$totalServices} services.");
    }
}
