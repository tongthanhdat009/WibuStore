<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolePermissionSeeder::class]);
        if (app()->environment('testing')) {
            return;
        }
        $this->call([DemoSeeder::class, PortfolioImageSeeder::class, PortfolioEnrichSeeder::class, SeedProductNamingSeeder::class, CollectionSeeder::class]);
    }
}
