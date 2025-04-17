<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\ApiKeySeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            APIKeySeeder::class,
        ]);
    }
}
