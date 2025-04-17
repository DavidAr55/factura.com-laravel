<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ApiKey;

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApiKey::create([
            'key'    => 'VUE-dfzo6FZOv7Z9629LLc9aO8PaIDxHFLks',
            'secret' => 'VUE-S-BC7DDD9F9DABC7F39F8BEEF721C33-1D1E3',
        ]);
    }
}
