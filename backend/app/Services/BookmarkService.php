<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookmarkService
{
    /**
     * @return bool true إذا أصبح المنشور محفوظاً، false إذا أُلغي الحفظ
     */
    public function toggleBookmark(Account $account, Post $post): bool
    {
        $userId = $account->profile()->id;

        $bookmark = Bookmark::where('post_id', $post->id)->where('user_id', $userId)->first();

        if ($bookmark) {
            $bookmark->delete();

            return false;
        }

        Bookmark::create(['user_id' => $userId, 'post_id' => $post->id]);

        return true;
    }

    /**
     * المنشورات المحفوظة (الأحدث حفظاً أولاً)
     */
    public function getBookmarkedPosts(Account $account): LengthAwarePaginator
    {
        $userId = $account->profile()->id;

        return Post::query()
            ->forResource($account)
            ->join('bookmarks', 'bookmarks.post_id', '=', 'posts.id')
            ->where('bookmarks.user_id', $userId)
            ->published()
            ->orderByDesc('bookmarks.id')
            ->paginate(10);
    }
}
