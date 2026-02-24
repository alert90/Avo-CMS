<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Food\Models\Food;
use Modules\Food\Models\FoodTranslation;
use Modules\Food\Models\FoodTerm;
use Modules\Food\Models\FoodDate;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample food data
        $foods = [
            [
                'title' => 'Grilled Octopus with Lemon Herb',
                'content' => 'Fresh octopus grilled to perfection with lemon, garlic, and Mediterranean herbs. A seafood delicacy that melts in your mouth.',
                'price' => 45.00,
                'sale_price' => 38.00,
                'location_id' => 12, // Zanzibar
                'address' => 'Stone Town, Zanzibar',
                'map_lat' => '-6.1659',
                'map_lng' => '39.2026',
                'start_time' => '30', // 30 minutes preparation
                'duration' => '1',
                'duration_unit' => 'hour',
                'is_featured' => 1,
                'status' => 'publish',
                'default_state' => 1,
                'ticket_types' => json_encode([
                    [
                        'name' => 'Adult Portion',
                        'name_en' => 'Adult Portion',
                        'price' => 45.00,
                        'number' => 20,
                        'min' => 1,
                        'max' => 10,
                        'code' => 'adult_portion'
                    ],
                    [
                        'name' => 'Child Portion',
                        'name_en' => 'Child Portion',
                        'price' => 25.00,
                        'number' => 15,
                        'min' => 0,
                        'max' => 5,
                        'code' => 'child_portion'
                    ]
                ]),
                'terms' => [106], // Octopus term
                'dates' => [
                    [
                        'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                        'end_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                        'active' => 1,
                        'ticket_types' => json_encode([
                            [
                                'name' => 'Adult Portion',
                                'name_en' => 'Adult Portion',
                                'price' => 45.00,
                                'number' => 20,
                                'code' => 'adult_portion'
                            ],
                            [
                                'name' => 'Child Portion',
                                'name_en' => 'Child Portion',
                                'price' => 25.00,
                                'number' => 15,
                                'code' => 'child_portion'
                            ]
                        ])
                    ]
                ]
            ],
            [
                'title' => 'Fresh Grilled Fish with Coconut Rice',
                'content' => 'Locally caught fish grilled with traditional spices, served with coconut rice and fresh tropical fruits.',
                'price' => 35.00,
                'sale_price' => null,
                'location_id' => 12, // Zanzibar
                'address' => 'Nungwi Beach, Zanzibar',
                'map_lat' => '-5.7265',
                'map_lng' => '39.2944',
                'start_time' => '25', // 25 minutes preparation
                'duration' => '45',
                'duration_unit' => 'minute',
                'is_featured' => 0,
                'status' => 'publish',
                'default_state' => 1,
                'ticket_types' => json_encode([
                    [
                        'name' => 'Regular Serving',
                        'name_en' => 'Regular Serving',
                        'price' => 35.00,
                        'number' => 25,
                        'min' => 1,
                        'max' => 8,
                        'code' => 'regular_serving'
                    ]
                ]),
                'terms' => [105], // Fish term
                'dates' => [
                    [
                        'start_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                        'end_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                        'active' => 1,
                        'ticket_types' => json_encode([
                            [
                                'name' => 'Regular Serving',
                                'name_en' => 'Regular Serving',
                                'price' => 35.00,
                                'number' => 25,
                                'code' => 'regular_serving'
                            ]
                        ])
                    ]
                ]
            ],
            [
                'title' => 'Traditional Tanzanian Breakfast',
                'content' => 'Start your day with authentic Tanzanian breakfast featuring fresh fruits, local breads, eggs, and traditional coffee.',
                'price' => 15.00,
                'sale_price' => 12.00,
                'location_id' => 11, // Dar Es Salaam
                'address' => 'City Center, Dar Es Salaam',
                'map_lat' => '-6.7924',
                'map_lng' => '39.2083',
                'start_time' => '15', // 15 minutes preparation
                'duration' => '30',
                'duration_unit' => 'minute',
                'is_featured' => 1,
                'status' => 'publish',
                'default_state' => 1,
                'ticket_types' => json_encode([
                    [
                        'name' => 'Full Breakfast',
                        'name_en' => 'Full Breakfast',
                        'price' => 15.00,
                        'number' => 30,
                        'min' => 1,
                        'max' => 10,
                        'code' => 'full_breakfast'
                    ]
                ]),
                'terms' => [], // Breakfast attribute (no specific terms)
                'dates' => [
                    [
                        'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                        'end_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                        'active' => 1,
                        'ticket_types' => json_encode([
                            [
                                'name' => 'Full Breakfast',
                                'name_en' => 'Full Breakfast',
                                'price' => 15.00,
                                'number' => 30,
                                'code' => 'full_breakfast'
                            ]
                        ])
                    ]
                ]
            ],
            [
                'title' => 'Swahili Brunch Special',
                'content' => 'A delightful brunch experience with Swahili flavors, fresh juices, and traditional pastries.',
                'price' => 22.00,
                'sale_price' => null,
                'location_id' => 15, // Arusha
                'address' => 'Downtown Arusha',
                'map_lat' => '-3.3869',
                'map_lng' => '36.6820',
                'start_time' => '20', // 20 minutes preparation
                'duration' => '1',
                'duration_unit' => 'hour',
                'is_featured' => 0,
                'status' => 'publish',
                'default_state' => 1,
                'ticket_types' => json_encode([
                    [
                        'name' => 'Brunch Set',
                        'name_en' => 'Brunch Set',
                        'price' => 22.00,
                        'number' => 20,
                        'min' => 1,
                        'max' => 6,
                        'code' => 'brunch_set'
                    ]
                ]),
                'terms' => [], // Brunch attribute
                'dates' => [
                    [
                        'start_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                        'end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                        'active' => 1,
                        'ticket_types' => json_encode([
                            [
                                'name' => 'Brunch Set',
                                'name_en' => 'Brunch Set',
                                'price' => 22.00,
                                'number' => 20,
                                'code' => 'brunch_set'
                            ]
                        ])
                    ]
                ]
            ],
            [
                'title' => 'Local Ugali with Vegetable Stew',
                'content' => 'Traditional Tanzanian ugali served with fresh vegetable stew and locally sourced ingredients.',
                'price' => 12.00,
                'sale_price' => 10.00,
                'location_id' => 13, // Morogoro
                'address' => 'Morogoro Town Center',
                'map_lat' => '-6.8210',
                'map_lng' => '37.6612',
                'start_time' => '35', // 35 minutes preparation
                'duration' => '45',
                'duration_unit' => 'minute',
                'is_featured' => 0,
                'status' => 'publish',
                'default_state' => 1,
                'ticket_types' => json_encode([
                    [
                        'name' => 'Standard Plate',
                        'name_en' => 'Standard Plate',
                        'price' => 12.00,
                        'number' => 40,
                        'min' => 1,
                        'max' => 15,
                        'code' => 'standard_plate'
                    ]
                ]),
                'terms' => [], // Local Food attribute
                'dates' => [
                    [
                        'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
                        'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                        'active' => 1,
                        'ticket_types' => json_encode([
                            [
                                'name' => 'Standard Plate',
                                'name_en' => 'Standard Plate',
                                'price' => 12.00,
                                'number' => 40,
                                'code' => 'standard_plate'
                            ]
                        ])
                    ]
                ]
            ]
        ];

        foreach ($foods as $foodData) {
            $terms = $foodData['terms'] ?? [];
            $dates = $foodData['dates'] ?? [];
            unset($foodData['terms'], $foodData['dates']);

            // Create slug
            $foodData['slug'] = Str::slug($foodData['title']);

            // Set author (use first admin user or create one)
            $foodData['create_user'] = 1255; // From the attributes we saw

            $food = Food::create($foodData);

            // Create dates
            foreach ($dates as $dateData) {
                $dateData['target_id'] = $food->id;
                FoodDate::create($dateData);
            }

            // Create terms
            foreach ($terms as $termId) {
                FoodTerm::create([
                    'target_id' => $food->id,
                    'term_id' => $termId
                ]);
            }
        }
    }
}
