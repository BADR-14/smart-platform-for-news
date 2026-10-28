<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReactTypeSeeder extends Seeder
{
    /**
     * المعرّفات ثابتة لأن الواجهات تعتمد عليها (1 = إعجاب ... 6 = غاضب)
     */
    public function run(): void
    {
        $reactTypes = [
            ['id' => 1, 'type' => 'like', 'icon' => '👍'],
            ['id' => 2, 'type' => 'love', 'icon' => '❤️'],
            ['id' => 3, 'type' => 'haha', 'icon' => '😂'],
            ['id' => 4, 'type' => 'wow', 'icon' => '😮'],
            ['id' => 5, 'type' => 'sad', 'icon' => '😢'],
            ['id' => 6, 'type' => 'angry', 'icon' => '😡'],
        ];

        foreach ($reactTypes as $react) {
            DB::table('react_types')->updateOrInsert(['id' => $react['id']], $react);
        }
    }
}
