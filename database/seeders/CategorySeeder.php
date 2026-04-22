<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        $categories = [
            'T-Shirts',
            'Shirts',
            'Pants',
            'Jeans',
            'Dresses',
            'Jackets',
            'Hoodies',
            'Sportswear',
            'Accessories',
            'Shoes',
            'Bags',
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
