<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebSiteNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('web_site_names')->insert([
            ['name' => 'https://www.gucci.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.louisvuitton.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.prada.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.versace.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.chanel.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.dior.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.ysl.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.balenciaga.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.fendi.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.cartier.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.nike.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.adidas.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.zalando.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.asos.com' , 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'https://www.mango.com' , 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
