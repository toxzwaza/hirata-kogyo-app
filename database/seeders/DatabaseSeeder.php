<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            StaffTypeSeeder::class,
            WorkMethodSeeder::class,
            DefectTypeSeeder::class,
            ClientSeeder::class,
            DrawingSeeder::class,
            WorkRateSeeder::class,
            StaffSeeder::class,
            // WorkRecordSeeder::class,
        ]);
    }
}
