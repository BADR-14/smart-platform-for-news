<?php

namespace Database\Seeders;

use App\Models\MediaType;
use Illuminate\Database\Seeder;

class MediaTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['image', 'video'] as $name) {
            MediaType::firstOrCreate(['name' => $name]);
        }
    }
}
