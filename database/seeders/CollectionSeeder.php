<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collections = [
            [
                'name' => 'Technology',
                'description' => 'Explore cutting-edge tech gadgets and accessories that elevate the gifting experience with practicality and innovation.',
                'slug' => 'technology',
                'image' => asset('backend/img/catalogue-item-1-QYBu9H8u.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Travel',
                'description' => 'Curate the perfect travel essentials, from sleek luggage to must-have accessories for business and leisure trips.',
                'slug' => 'travel',
                'image' => asset('backend/img/catalogue-item-2-BgohMJxX.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Food & Beverage',
                'description' => 'Delight your recipients with gourmet food gifts and premium beverages, perfect for any occasion or milestone.',
                'slug' => 'food-beverage',
                'image' => asset('backend/img/catalogue-item-3-CzTDAV9p.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Stationery',
                'description' => 'Elevate your corporate image with bespoke stationery items, including luxury pens, notepads, and organizers, designed for professionalism and style.',
                'slug' => 'stationery',
                'image' => asset('backend/img/catalogue-item-4-7dNOCLQ.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Wearables',
                'description' => 'Offer stylish, customizable wearables, from branded clothing to accessories, ensuring your gifts make a lasting impact.',
                'slug' => 'wearables',
                'image' => asset('backend/img/catalogue-item-5-Do8Op1jm.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Wellness & Holistic',
                'description' => 'Promote relaxation and well-being with wellness gifts, including spa essentials, aromatherapy, and holistic self-care products.',
                'slug' => 'wellness-holistic',
                'image' => asset('backend/img/catalogue-item-6-xLNwOiA0.png'),
                'status' => 'active'
            ],

            [
                'name' => 'Games & desk gifts',
                'description' => 'Add fun and functionality to workspaces with personalized games and desk gifts that offer both relaxation and productivity.',
                'slug' => 'games-desk-gifts',
                'image' => asset('backend/img/catalogue-item-7-D0UjZWFx.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Alcohol',
                'description' => 'Raise a toast with premium wines, spirits, and craft beers, all customizable with branded packaging for a truly memorable gift.',
                'slug' => 'alcohol',
                'image' => asset('backend/img/catalogue-item-8-CH2UJSh.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Branding',
                'description' => 'Customize your corporate gifts with your brand’s unique identity, from logo branding to tailored packaging solutions that make a statement.',
                'slug' => 'branding',
                'image' => asset('backend/img/catalogue-item-9-RduDdV9k.png'),
                'status' => 'active'
            ],
            [
                'name' => 'Accessories',
                'description' => 'Discover a wide range of stylish and functional accessories, from tech organizers to jewelry, perfect for elevating any gifting experience.',
                'slug' => 'accessories',
                'image' => asset('backend/img/catalogue-item-9-RduDdV9k.png'),
                'status' => 'active'
            ]
        ];

        foreach ($collections as $collection) {
            Collection::create($collection);
        }
    }
}
