<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kuliner',
                'slug' => 'kuliner',
                'description' => 'Makanan, minuman, camilan, kafe, dan katering.',
            ],
            [
                'name' => 'Fashion & Busana',
                'slug' => 'fashion',
                'description' => 'Pakaian pria/wanita, hijab, aksesoris, sepatu, dan tas.',
            ],
            [
                'name' => 'Kecantikan & Kesehatan',
                'slug' => 'kecantikan',
                'description' => 'Produk skincare, kosmetik, herbal, dan perawatan tubuh.',
            ],
            [
                'name' => 'Kerajinan Tangan & Kriya',
                'slug' => 'kerajinan',
                'description' => 'Kerajinan kayu, anyaman, keramik, batik, dan perhiasan lokal.',
            ],
            [
                'name' => 'Jasa & Layanan',
                'slug' => 'jasa',
                'description' => 'Fotografi, laundry, perbaikan, desain, dan konsultasi.',
            ],
        ];

        foreach ($categories as $category) {
            BusinessCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
