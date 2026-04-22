<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(StyleQuizSeeder::class);
        // $this->call(PlanSeeder::class);
        $this->call(WebSiteNameSeeder::class);
    }
}
