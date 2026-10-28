<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'User', 'description' => 'Regular system user'],
            ['id' => 2, 'name' => 'Company', 'description' => 'Business provider'],
            ['id' => 3, 'name' => 'Admin', 'description' => 'System administrator'],
        ];
        foreach ($types as $type) {
            DB::table('account_types')->updateOrInsert(
                ['id' => $type['id']],
                $type
            );
        }
    }
}
