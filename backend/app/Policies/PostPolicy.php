<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Post;

class PostPolicy
{
    /**
     * المنشور المنشور يراه الجميع (حتى الزائر)، وغير المنشور يراه صاحبه والأدمن فقط
     */
    public function view(?Account $account, Post $post): bool
    {
        return $post->isPublished()
            || $post->isOwnedBy($account)
            || (bool) $account?->isAdmin();
    }

    public function update(Account $account, Post $post): bool
    {
        return $post->isOwnedBy($account);
    }

    public function delete(Account $account, Post $post): bool
    {
        return $post->isOwnedBy($account) || $account->isAdmin();
    }
}
