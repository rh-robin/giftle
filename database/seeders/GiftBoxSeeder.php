<?php

namespace Database\Seeders;

use App\Models\GiftBox;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GiftBoxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GiftBox::create([
            'name' => 'Gift Box',
            'gifte_branded_price' => 100,
            'custom_branding_price' => 150,
            'plain_price' => 50,
            'image' => 'backend/img/automated.png',
        ]);
        GiftBox::create([
            'name' => 'Craft Gift Boxes',
            'gifte_branded_price' => 30,
            'custom_branding_price' => 150,
            'plain_price' => 50,
            'image' => 'backend/img/automated.png',
        ]);
        GiftBox::create([
            'name' => 'Luxury gift box',
            'gifte_branded_price' => 100,
            'custom_branding_price' => 150,
            'plain_price' => 50,
            'image' => 'backend/img/automated.png',
        ]);
        GiftBox::create([
            'name' => 'Packing tape',
            'gifte_branded_price' => 100,
            'custom_branding_price' => 150,
            'plain_price' => 50,
            'image' => 'backend/img/automated.png',
        ]);
        GiftBox::create([
            'name' => 'Paper Polymaile',
            'gifte_branded_price' => 100,
            'custom_branding_price' => 150,
            'plain_price' => 50,
            'image' => 'backend/img/automated.png',
        ]);
        GiftBox::create([
            'name' => 'Poly mailer',
            'gifte_branded_price' => 100,
            'custom_branding_price' => 150,
            'plain_price' => 50,
            'image' => 'backend/img/automated.png',
        ]);
    }
}
