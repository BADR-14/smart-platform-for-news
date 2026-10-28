<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Comment;

class CommentPolicy
{
    /**
     * يحذف التعليق: صاحبه، أو صاحب المنشور، أو الأدمن
     */
    public function delete(Account $account, Comment $comment): bool
    {
        return (int) $comment->user?->account_id === (int) $account->id
            || (bool) $comment->post?->isOwnedBy($account)
            || $account->isAdmin();
    }
}
