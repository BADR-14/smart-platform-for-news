<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\MediaType;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * يضيف صورة غلاف لكل منشور تجريبي ليس له صور.
 * الصور جاهزة في database/seeders/images/post-covers/{رقم المنشور}.jpg
 *
 * آمن للتشغيل أكثر من مرة: المنشور الذي له صورة لا يُمَس.
 */
class PostCoverSeeder extends Seeder
{
    public function run(): void
    {
        $imageType = MediaType::firstOrCreate(['name' => 'image']);
        $source = database_path('seeders/images/post-covers');

        Post::doesntHave('media')->get()->each(function (Post $post) use ($imageType, $source) {
            $file = "{$source}/{$post->id}.jpg";

            if (! File::exists($file)) {
                return;
            }

            $path = "posts/{$post->id}/cover.jpg";
            Storage::disk('public')->put($path, File::get($file));

            Media::create([
                'account_id' => $post->account_id,
                'post_id' => $post->id,
                'media_type_id' => $imageType->id,
                'media_url' => $path,
            ]);
        });
    }
}
