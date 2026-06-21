<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Models\HomepageSection;
use App\Models\StorageConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        // ----- Storage configuration (inactive until credentials added) -----
        StorageConfiguration::updateOrCreate(
            ['name' => 'Primary (Cloudflare R2)'],
            [
                'provider' => 'r2',
                'disk' => 'r2',
                'use_path_style' => true,
                'is_active' => false,
                'cost_per_gb' => 0.015,
            ],
        );

        // ----- Categories -----
        $categories = [
            ['Originals', 'free', 0, null, 'Exclusive originals available to everyone.'],
            ['Premium Series', 'subscription', 299, 30, 'Binge-worthy premium series, renewed monthly.'],
            ['Masterclasses', 'paid', 499, 365, 'In-depth masterclasses with one-time yearly access.'],
            ['Lifetime Vault', 'lifetime', 1999, null, 'Pay once, watch forever.'],
        ];

        $created = [];
        foreach ($categories as $i => [$name, $accessType, $price, $validity, $desc]) {
            $created[] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'sort_order' => $i,
                    'is_active' => true,
                    'access_type' => $accessType,
                    'price' => $price,
                    'currency' => 'INR',
                    'validity_days' => $validity,
                    'preview_seconds' => 20,
                    'seo_title' => $name.' | Maal',
                    'meta_description' => $desc,
                ],
            );
        }

        // ----- Access plans for non-free categories -----
        $premium = $created[1];
        CategoryAccessPlan::updateOrCreate(
            ['category_id' => $premium->id, 'type' => 'monthly'],
            ['name' => 'Premium Monthly', 'price' => 299, 'currency' => 'INR', 'validity_days' => 30, 'is_active' => true, 'is_featured' => true],
        );
        CategoryAccessPlan::updateOrCreate(
            ['category_id' => $premium->id, 'type' => 'yearly'],
            ['name' => 'Premium Yearly', 'price' => 2499, 'compare_at_price' => 3588, 'currency' => 'INR', 'validity_days' => 365, 'is_active' => true],
        );

        $masterclass = $created[2];
        CategoryAccessPlan::updateOrCreate(
            ['category_id' => $masterclass->id, 'type' => 'yearly'],
            ['name' => 'Masterclasses Yearly', 'price' => 499, 'currency' => 'INR', 'validity_days' => 365, 'is_active' => true],
        );

        $vault = $created[3];
        CategoryAccessPlan::updateOrCreate(
            ['category_id' => $vault->id, 'type' => 'lifetime'],
            ['name' => 'Lifetime Vault Access', 'price' => 1999, 'currency' => 'INR', 'validity_days' => null, 'is_active' => true, 'is_featured' => true],
        );

        // Bundle plan across premium + masterclasses + vault
        CategoryAccessPlan::updateOrCreate(
            ['category_id' => null, 'type' => 'bundle'],
            [
                'name' => 'All-Access Bundle',
                'description' => 'Unlock Premium Series, Masterclasses and the Lifetime Vault.',
                'price' => 2999,
                'compare_at_price' => 4997,
                'currency' => 'INR',
                'validity_days' => 365,
                'bundle_category_ids' => [$premium->id, $masterclass->id, $vault->id],
                'is_active' => true,
                'is_featured' => true,
            ],
        );

        // ----- Homepage sections -----
        $sections = [
            ['Featured Categories', 'featured_categories', 'hero', 0],
            ['Trending Now', 'trending', 'carousel', 1],
            ['Continue Watching', 'continue_watching', 'carousel', 2],
            ['Newest Uploads', 'newest', 'carousel', 3],
            ['Recently Added', 'recently_added', 'grid', 4],
            ['Recommended For You', 'recommended', 'carousel', 5],
        ];
        foreach ($sections as [$title, $type, $layout, $order]) {
            HomepageSection::updateOrCreate(
                ['type' => $type],
                ['title' => $title, 'layout' => $layout, 'sort_order' => $order, 'item_limit' => 12, 'is_active' => true],
            );
        }

        // ----- Hero banner -----
        Banner::updateOrCreate(
            ['title' => 'Welcome to Maal'],
            [
                'subtitle' => 'Premium streaming, unlocked by category.',
                'image' => 'banners/hero-default.jpg',
                'cta_label' => 'Browse Categories',
                'link_url' => '/categories',
                'placement' => 'hero',
                'sort_order' => 0,
                'is_active' => true,
            ],
        );
    }
}
