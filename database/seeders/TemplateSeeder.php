<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Minimalist Store',
                'slug' => 'minimalist',
                'thumbnail' => 'templates/minimalist.png',
                'view_path' => 'templates.minimalist.index',
                'description' => 'Template bersih dan elegan untuk produk fashion, aksesoris, dan kerajinan tangan.',
                'is_active' => true,
            ],
            [
                'name' => 'Food & Beverage',
                'slug' => 'food',
                'thumbnail' => 'templates/food.png',
                'view_path' => 'templates.food.index',
                'description' => 'Template menggugah selera untuk resto, kafe, bakery, dan usaha kuliner.',
                'is_active' => true,
            ],
            [
                'name' => 'Modern Business',
                'slug' => 'modern',
                'thumbnail' => 'templates/modern.png',
                'view_path' => 'templates.modern.index',
                'description' => 'Template modern dan profesional untuk skincare, herbal, ritel, dan aneka jasa.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
