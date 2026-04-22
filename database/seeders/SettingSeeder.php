<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->insert([
            'title'         => 'Chique',
            'phone'         => '123456789',
            'email'         => 'chique@gmail.com',
            'name'          => 'Riya ky',
            'copyright'     => 'Copyright © 2025 Chique. All rights reserved.',
            'description' => "Our platform helps users make faster and more confident outfit decisions by understanding their personal style.
                                Through an initial style quiz and smart AI assistance, users receive personalized outfit suggestions based on their preferences,
                                wardrobe, weather, and destination. With the ability to upload reference looks or existing outfits, users get c
                                urated styling advice and access to online shopping platforms. Acting like a personal stylist, the platform evolves with user tastes
                                and delivers a seamless fashion experience—all enhanced by a subscription model designed for flexibility and value.",

            'address'       => 'Cairo, Australia',
            'keywords'      => 'Chique, Digital Agency, Startup, Small Business, Web Development, Design',
            'author'        => 'Ria Ky',
            'logo'          => 'uploads/settings/logo.png',
            'favicon'       => 'uploads/settings/favicon.png',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
