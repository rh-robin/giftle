<?php

namespace Database\Seeders;

use App\Models\Gifting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GiftingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gifting = [
            [
                'name' => 'Welcome Packs',
                'description' => 'Create a lasting first impression with thoughtfully curated welcome packs that make new employees or clients feel valued from day one.',
                'image' => asset('backend/img/gift-package-1-Cg0gNPzi.png.png'),
                'slug' => 'welcome-packs',
                'status' => 'active',
            ],
            [
                'name' => 'Milestones',
                'description' => 'Celebrate key achievements and work anniversaries with personalized gifts that recognize dedication and success.',
                'image' => asset('backend/img/gift-package-2-B_nXYNio.png'),
                'slug' => 'milestones',
                'status' => 'active',
            ],
            [
                'name' => 'Event & Offsite Gifting',
                'description' => 'Enhance your events and retreats with bespoke gifts that leave a lasting impression on attendees.',
                'image' => asset('backend/img/gift-package-3-U8yye1PM.png'),
                'slug' => 'event-offsite-gifting',
                'status' => 'active',
            ],
            [
                'name' => 'Incentives & Awards',
                'description' => 'Motivate and reward top performers with high-quality gifts that inspire continued excellence.',
                'image' => asset('backend/img/gift-package-4-BlP69XMS.png'),
                'slug' => 'incentives-awards',
                'status' => 'active',
            ],
            [
                'name' => 'Conference Giveaways',
                'description' => 'Elevate your brand presence with memorable conference giveaways that attendees will love and use.',
                'image' => asset('backend/img/gift-package-5-rCfev1FC.png'),
                'slug' => 'microsite-gifting',
                'status' => 'active',
            ],
            [
                'name' => 'Influencer Gifting',
                'description' => 'Send curated gifts that fit your influencers’ lifestyles—making it easy for them to showcase your products authentically.',
                'image' => asset('backend/img/gift-package-6-6n7KKEot.png'),
                'slug' => 'gift-redemption',
                'status' => 'active',
            ],
            [
                'name' => 'Additional Event Branding Material',
                'description' => 'Strengthen your event’s impact with branded merchandise and packaging that seamlessly aligns with your company identity.',
                'image' => asset('backend/img/gift-package-7-CcAYK3vq.png'),
                'slug' => 'gifting-management-tools',
                'status' => 'active',
            ],
        ];

        foreach ($gifting as $item) {
            Gifting::create($item);
        }
    }
}
