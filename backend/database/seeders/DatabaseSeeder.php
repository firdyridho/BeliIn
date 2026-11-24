<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\UserAddress;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Seller\Models\Seller;
use App\Modules\Seller\Models\Store;
use App\Modules\Shipping\Models\ShippingProvider;
use App\Modules\Shipping\Models\ShippingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator', 'description' => 'Marketplace administrator']);
        $sellerRole = Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller', 'description' => 'Verified store merchant']);
        $buyerRole = Role::firstOrCreate(['slug' => 'buyer'], ['name' => 'Buyer', 'description' => 'Regular customer']);

        // 2. Demo Users & Accounts
        // A. Admin Demo
        $admin = User::updateOrCreate(
            ['email' => 'admin@beliin.test'],
            [
                'name' => 'BeliIn Administrator',
                'phone' => '081100000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        // B. Buyer Demo
        $buyer = User::updateOrCreate(
            ['email' => 'buyer@beliin.test'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081298765432',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $buyer->roles()->syncWithoutDetaching([$buyerRole->id]);

        // Buyer Addresses
        UserAddress::updateOrCreate(
            ['user_id' => $buyer->id, 'is_default' => true],
            [
                'recipient_name' => 'Budi Santoso',
                'phone' => '081298765432',
                'address_line' => 'Gedung Menara Sudirman Lt. 14, Jl. Jend. Sudirman Kav. 60, Senayan',
                'province' => 'DKI Jakarta',
                'city' => 'Jakarta Selatan',
                'district' => 'Kebayoran Baru',
                'village' => 'Senayan',
                'postal_code' => '12190',
                'is_default' => true,
            ]
        );

        UserAddress::updateOrCreate(
            ['user_id' => $buyer->id, 'postal_code' => '40135'],
            [
                'recipient_name' => 'Budi Santoso (Rumah Bandung)',
                'phone' => '081298765432',
                'address_line' => 'Jl. Dago Asri No. 18, Coblong',
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'district' => 'Coblong',
                'village' => 'Dago',
                'postal_code' => '40135',
                'is_default' => false,
            ]
        );

        // C. Seller Demo 1: Nusantara Gadget Store
        $sellerUser1 = User::updateOrCreate(
            ['email' => 'seller@beliin.test'],
            [
                'name' => 'Hendro Wijaya',
                'phone' => '081211112222',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $sellerUser1->roles()->syncWithoutDetaching([$sellerRole->id]);
        $seller1 = Seller::updateOrCreate(['user_id' => $sellerUser1->id], ['status' => 'verified', 'verified_at' => now()]);
        $store1 = Store::updateOrCreate(
            ['seller_id' => $seller1->id],
            [
                'name' => 'Nusantara Gadget Store',
                'slug' => 'nusantara-gadget',
                'description' => 'Pusat gadget, aksesoris audio nirkabel, dan periferal produktivitas resmi bergaransi 1 tahun.',
                'logo_path' => 'logos/beliin-logo-dummy.svg',
                'status' => 'active',
                'rating_summary' => 4.95,
            ]
        );

        // D. Seller Demo 2: AnterIn Official Apparel & Gear
        $sellerUser2 = User::updateOrCreate(
            ['email' => 'logistics@anterin.com'],
            [
                'name' => 'Rian AnterIn Merchant',
                'phone' => '081233334444',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $sellerUser2->roles()->syncWithoutDetaching([$sellerRole->id]);
        $seller2 = Seller::updateOrCreate(['user_id' => $sellerUser2->id], ['status' => 'verified', 'verified_at' => now()]);
        $store2 = Store::updateOrCreate(
            ['seller_id' => $seller2->id],
            [
                'name' => 'AnterIn Gear & Merchandise',
                'slug' => 'anterin-gear-official',
                'description' => 'Perlengkapan ekspedisi, jaket outdoor anti-angin tahan cuaca ekstrem, tas ransel kurir, dan merchandise resmi AnterIn Express.',
                'logo_path' => 'logos/anterin-logo-dummy.svg',
                'status' => 'active',
                'rating_summary' => 4.88,
            ]
        );

        // 3. Shipping Providers & Services
        $anterin = ShippingProvider::updateOrCreate(
            ['code' => 'anterin'],
            [
                'name' => 'AnterIn Express',
                'provider_type' => 'internal',
                'is_active' => true,
                'config_reference' => ['logo' => 'logos/anterin-logo-dummy.svg'],
            ]
        );

        ShippingService::updateOrCreate(
            ['shipping_provider_id' => $anterin->id, 'service_code' => 'reg'],
            ['service_name' => 'AnterIn Regular (1-2 hari)', 'estimated_min_days' => 1, 'estimated_max_days' => 2, 'is_active' => true]
        );
        ShippingService::updateOrCreate(
            ['shipping_provider_id' => $anterin->id, 'service_code' => 'sameday'],
            ['service_name' => 'AnterIn Same Day (6-8 jam)', 'estimated_min_days' => 0, 'estimated_max_days' => 1, 'is_active' => true]
        );
        ShippingService::updateOrCreate(
            ['shipping_provider_id' => $anterin->id, 'service_code' => 'instant'],
            ['service_name' => 'AnterIn Instant (1-2 jam)', 'estimated_min_days' => 0, 'estimated_max_days' => 0, 'is_active' => true]
        );

        $jne = ShippingProvider::updateOrCreate(
            ['code' => 'jne'],
            ['name' => 'JNE Express', 'provider_type' => 'external', 'is_active' => true]
        );
        ShippingService::updateOrCreate(
            ['shipping_provider_id' => $jne->id, 'service_code' => 'reg'],
            ['service_name' => 'JNE Reguler (2-3 hari)', 'estimated_min_days' => 2, 'estimated_max_days' => 3, 'is_active' => true]
        );

        // 4. Categories
        $catGadget = Category::updateOrCreate(['slug' => 'elektronik'], ['name' => 'Elektronik & Gadget', 'sort_order' => 1]);
        $catFashionPria = Category::updateOrCreate(['slug' => 'fashion-pria'], ['name' => 'Fashion Pria', 'sort_order' => 2]);
        $catFashionWanita = Category::updateOrCreate(['slug' => 'fashion-wanita'], ['name' => 'Fashion Wanita', 'sort_order' => 3]);
        $catHome = Category::updateOrCreate(['slug' => 'kebutuhan-rumah'], ['name' => 'Kebutuhan Rumah & Dapur', 'sort_order' => 4]);
        $catGear = Category::updateOrCreate(['slug' => 'aksesoris-olahraga'], ['name' => 'Aksesoris & Outdoor', 'sort_order' => 5]);

        // 5. Authentic Products Seeding (Store 1: Nusantara Gadget)
        // Product 1: TWS Earbuds ANC
        $p1 = Product::updateOrCreate(
            ['slug' => 'pro-anc-wireless-earbuds-gen3'],
            [
                'store_id' => $store1->id,
                'category_id' => $catGadget->id,
                'name' => 'Pro ANC Wireless Earbuds Gen-3 Hi-Res Audio',
                'description' => "Earbuds nirkabel generasi ke-3 dengan teknologi Active Noise Cancelling (ANC) hingga 42dB, driver titanium 11mm untuk suara bass yang bertenaga dan vokal jernih.\n\nSpesifikasi:\n- Bluetooth 5.3 Low Latency (45ms Game Mode)\n- Baterai hingga 38 Jam (dengan Charging Case)\n- IPX5 Water & Sweat Resistant\n- Garansi Resmi 12 Bulan Ganti Baru",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 180,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p1->id, 'file_path' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'Pro ANC Earbuds Charging Case']
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p1->id, 'file_path' => 'https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 2, 'alt_text' => 'Earbuds Detail']
        );
        $v1_1 = ProductVariant::updateOrCreate(
            ['sku' => 'TWS-ANC3-BLK'],
            ['product_id' => $p1->id, 'variant_name' => 'Matte Black', 'price_amount' => 429000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v1_1->id], ['available_quantity' => 60, 'reserved_quantity' => 0, 'sold_quantity' => 12]);
        $v1_2 = ProductVariant::updateOrCreate(
            ['sku' => 'TWS-ANC3-WHT'],
            ['product_id' => $p1->id, 'variant_name' => 'Ceramic White', 'price_amount' => 449000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v1_2->id], ['available_quantity' => 45, 'reserved_quantity' => 0, 'sold_quantity' => 8]);

        // Product 2: Mechanical Keyboard Wireless 75%
        $p2 = Product::updateOrCreate(
            ['slug' => 'artisan-75-mechanical-keyboard-wireless'],
            [
                'store_id' => $store1->id,
                'category_id' => $catGadget->id,
                'name' => 'Artisan 75 Mechanical Keyboard Tri-Mode Wireless Hot-Swap',
                'description' => "Keyboard mekanikal layout 75% dengan gasket mount, knob volume aluminium, dan switch factory pre-lubed yang empuk dan thocky.\n\nKonektivitas:\n- Bluetooth 5.0 (3 Device Pairing)\n- 2.4GHz Wireless Dongle\n- USB-C Wired Detachable\n- Baterai 4000mAh tahan hingga 200 jam.",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 950,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p2->id, 'file_path' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'Artisan 75 Mechanical Keyboard']
        );
        $v2_1 = ProductVariant::updateOrCreate(
            ['sku' => 'KB-75-RED'],
            ['product_id' => $p2->id, 'variant_name' => 'Linear Red Switch (Quiet & Smooth)', 'price_amount' => 899000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v2_1->id], ['available_quantity' => 35, 'reserved_quantity' => 0, 'sold_quantity' => 5]);
        $v2_2 = ProductVariant::updateOrCreate(
            ['sku' => 'KB-75-BRW'],
            ['product_id' => $p2->id, 'variant_name' => 'Tactile Brown Switch (Balanced)', 'price_amount' => 899000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v2_2->id], ['available_quantity' => 28, 'reserved_quantity' => 0, 'sold_quantity' => 9]);

        // Product 3: Smart LED Minimalist Desk Lamp
        $p3 = Product::updateOrCreate(
            ['slug' => 'lumina-smart-desk-lamp-minimalist'],
            [
                'store_id' => $store1->id,
                'category_id' => $catHome->id,
                'name' => 'Lumina Smart LED Desk Lamp Eye-Care with Wireless Charger',
                'description' => "Lampu meja arsitek modern dengan pencahayaan anti-blue light (CRI 95+), kontrol touch dimmer, temperatur warna 2700K - 6500K, dan wireless fast charging pad 15W di bagian base.",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 820,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p3->id, 'file_path' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'Lumina Desk Lamp']
        );
        $v3_1 = ProductVariant::updateOrCreate(
            ['sku' => 'LMP-LUM-SLV'],
            ['product_id' => $p3->id, 'variant_name' => 'Space Grey / Silver', 'price_amount' => 375000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v3_1->id], ['available_quantity' => 40, 'reserved_quantity' => 0, 'sold_quantity' => 4]);

        // Product 4: Kemeja Linen Casual Pria
        $p4 = Product::updateOrCreate(
            ['slug' => 'urban-linen-casual-shirt-short-sleeve'],
            [
                'store_id' => $store1->id,
                'category_id' => $catFashionPria->id,
                'name' => 'Urban Linen Casual Shirt Lengan Pendek Premium Breathable',
                'description' => "Kemeja berbahan 100% French Linen alami yang sangat adem, ringan, dan menyerap keringat. Potongan Relaxed Fit yang nyaman untuk iklim tropis Indonesia.",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 240,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p4->id, 'file_path' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'Urban Linen Shirt']
        );
        $v4_1 = ProductVariant::updateOrCreate(
            ['sku' => 'SHR-LIN-SND-M'],
            ['product_id' => $p4->id, 'variant_name' => 'Sand Beige / Size M', 'price_amount' => 219000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v4_1->id], ['available_quantity' => 50, 'reserved_quantity' => 0, 'sold_quantity' => 15]);
        $v4_2 = ProductVariant::updateOrCreate(
            ['sku' => 'SHR-LIN-SND-L'],
            ['product_id' => $p4->id, 'variant_name' => 'Sand Beige / Size L', 'price_amount' => 219000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v4_2->id], ['available_quantity' => 40, 'reserved_quantity' => 0, 'sold_quantity' => 10]);

        // 6. Authentic Products Seeding (Store 2: AnterIn Gear & Merchandise)
        // Product 5: AnterIn Windproof & Waterproof Courier Jacket
        $p5 = Product::updateOrCreate(
            ['slug' => 'anterin-expedition-courier-jacket-waterproof'],
            [
                'store_id' => $store2->id,
                'category_id' => $catFashionPria->id,
                'name' => 'AnterIn Expedition Jacket All-Weather Waterproof & Windproof',
                'description' => "Jaket kurir profesional AnterIn Express dengan material Taslan Milky 3-Layer tahan air hujan lebat dan angin kencang. Dilengkapi 3M Scotchlite reflective stripes untuk keselamatan berkendara di malam hari.",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 580,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p5->id, 'file_path' => 'https://images.unsplash.com/photo-1544441893-675973e31985?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'AnterIn Jacket']
        );
        $v5_1 = ProductVariant::updateOrCreate(
            ['sku' => 'ANT-JKT-L'],
            ['product_id' => $p5->id, 'variant_name' => 'Courier Orange / Size L', 'price_amount' => 285000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v5_1->id], ['available_quantity' => 70, 'reserved_quantity' => 0, 'sold_quantity' => 20]);
        $v5_2 = ProductVariant::updateOrCreate(
            ['sku' => 'ANT-JKT-XL'],
            ['product_id' => $p5->id, 'variant_name' => 'Courier Orange / Size XL', 'price_amount' => 285000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v5_2->id], ['available_quantity' => 55, 'reserved_quantity' => 0, 'sold_quantity' => 18]);

        // Product 6: Roll-Top Waterproof Messenger Backpack 28L
        $p6 = Product::updateOrCreate(
            ['slug' => 'anterin-roll-top-courier-backpack-28l'],
            [
                'store_id' => $store2->id,
                'category_id' => $catGear->id,
                'name' => 'AnterIn Roll-Top Commuter Backpack 28L Waterproof Cordura',
                'description' => "Tas ransel roll-top berkapasitas 28 liter dengan kompartemen laptop 16 inci terisolasi busa EVA tebal. Bahan Cordura 1000D bersertifikat tahan robek dan anti air.",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 750,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p6->id, 'file_path' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'AnterIn Roll-Top Backpack']
        );
        $v6_1 = ProductVariant::updateOrCreate(
            ['sku' => 'ANT-BPK-BLK'],
            ['product_id' => $p6->id, 'variant_name' => 'Stealth Black 28L', 'price_amount' => 349000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v6_1->id], ['available_quantity' => 45, 'reserved_quantity' => 0, 'sold_quantity' => 11]);

        // Product 7: Vacuum Insulated Thermal Tumbler 750ml
        $p7 = Product::updateOrCreate(
            ['slug' => 'anterin-hydro-thermal-tumbler-750ml'],
            [
                'store_id' => $store2->id,
                'category_id' => $catGear->id,
                'name' => 'AnterIn Hydro Thermal Tumbler 750ml Stainless Steel 316',
                'description' => "Tumbler termal dinding ganda (double-wall vacuum) berbahan Medical Grade Stainless Steel 316. Menjaga minuman es dingin hingga 24 jam dan panas hingga 12 jam.",
                'condition' => 'new',
                'status' => 'published',
                'weight_gram' => 380,
                'published_at' => now(),
            ]
        );
        ProductImage::updateOrCreate(
            ['product_id' => $p7->id, 'file_path' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=800&auto=format&fit=crop&q=80'],
            ['sort_order' => 1, 'alt_text' => 'Hydro Thermal Tumbler']
        );
        $v7_1 = ProductVariant::updateOrCreate(
            ['sku' => 'ANT-TMB-ORG'],
            ['product_id' => $p7->id, 'variant_name' => 'AnterIn Sunset Orange 750ml', 'price_amount' => 165000, 'status' => 'active']
        );
        Inventory::updateOrCreate(['product_variant_id' => $v7_1->id], ['available_quantity' => 80, 'reserved_quantity' => 0, 'sold_quantity' => 25]);
    }
}
