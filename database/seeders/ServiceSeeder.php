<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Design',
                'description' => 'Our design team works closely with you to create bespoke gifting solutions that perfectly reflect your brand. Whether you need a fully branded gift set, custom packaging, or personalized items, we tailor every element to your specifications.',
                'image' => 'backend/img/design.png',
                'slug' => 'design',
                'status' => 'active',
            ],
            [
                'name' => 'Fulfillment',
                'description' => 'Once your gifts are selected and designed, we take care of the entire fulfillment process. Our team ensures that all gifts are carefully packaged and securely prepared for shipping, whether it’s to a single location or multiple addresses.',
                'image' => 'backend/img/fulfillment.ng',
                'slug' => 'fulfillment',
                'status' => 'active',
            ],
            [
                'name' => 'Storage Inventory',
                'description' => 'We offer secure storage solutions to keep your inventory safe and accessible to you. Whether you need to store a small batch of branded items or large quantities of gifts for upcoming campaigns, we can store your products in our secure facilities. With real-time inventory tracking, you can easily manage your stock levels and plan for future gifting events without worrying about storage capacity.',
                'image' => 'backend/img/storage.png',
                'slug' => 'storage-inventory',
                'status' => 'active',
            ],
            [
                'name' => 'Automated Gifting',
                'description' => 'Our automated gifting system allows you to set up recurring gifting schedules based on key dates, such as birthdays, anniversaries, or performance milestones. Once set up, the system takes care of everything—from gift selection to delivery—ensuring that gifts are sent out on time, every time.',
                'image' => 'backend/img/automated.pn',
                'slug' => 'automated-gifting',
                'status' => 'active',
            ],
            [
                'name' => 'Microsites',
                'description' => 'Create customized microsites for your gifting campaigns, allowing recipients to choose their preferred gifts or provide necessary details for personalization. Microsites enhance the gifting experience by offering a tailored interaction for each recipient.',
                'image' => 'backend/img/microsites.pg',
                'slug' => 'microsites',
                'status' => 'active',
            ],
            [
                'name' => 'Gift Redemption',
                'description' => 'Enable your recipients to redeem gifts at their convenience through a simple and user-friendly platform. Gift Redemption ensures flexibility and satisfaction by allowing recipients to select gifts that best suit their preferences and needs.',
                'image' => 'backend/img/redemption.pg',
                'slug' => 'gift-redemption',
                'status' => 'active',
            ],
            [
                'name' => 'Influencer Gifting/PR Box',
                'description' => 'Engage influencers and enhance your brand visibility with curated PR boxes. Our service helps you create impactful gifting experiences that resonate with influencers and their audiences, driving brand awareness and loyalty.',
                'image' => 'backend/img/influencer.pg',
                'slug' => 'influencer-gifting',
                'status' => 'active',
            ],
            [
                'name' => 'Gifting Management Tools',
                'description' => 'Leverage our advanced gifting management tools to streamline and automate your gifting operations. From tracking orders to managing budgets and analyzing campaign performance, our tools provide comprehensive support for all your gifting needs.',
                'image' => 'backend/img/tools.png',
                'slug' => 'gifting-management-tools',
                'status' => 'active',
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

    }
}
