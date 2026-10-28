<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Post;
use App\Models\PostView;

class PostViewService
{
    /**
     * يسجّل مشاهدة واحدة لكل حساب (أو لكل IP للزائر) ويزيد العدّاد عند أول مشاهدة فقط.
     * مشاهدة صاحب المنشور لمنشوره لا تُحتسب.
     */
    public function record(Post $post, ?Account $viewer, ?string $ip): void
    {
        if (! $post->isPublished() || $post->isOwnedBy($viewer)) {
            return;
        }

        $view = $viewer
            ? PostView::firstOrCreate(['post_id' => $post->id, 'account_id' => $viewer->id], ['ip_address' => $ip])
            : PostView::firstOrCreate(['post_id' => $post->id, 'account_id' => null, 'ip_address' => $ip]);

        if ($view->wasRecentlyCreated) {
            $post->increment('views_count');
        }
    }
}
