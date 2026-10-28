<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Exceptions\PostNotPublishedException;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CommentService
{
    private const AUTHOR_RELATIONS = ['user.account.accountType', 'user.account.company'];

    public function __construct(private readonly NotificationService $notificationService) {}

    public function getComments(Post $post): LengthAwarePaginator
    {
        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(self::AUTHOR_RELATIONS)
            ->withCount('replies')
            ->latest()
            ->paginate(10);

        return $this->attachPost($comments, $post);
    }

    public function getReplies(Post $post, Comment $comment): LengthAwarePaginator
    {
        $this->ensureBelongsToPost($post, $comment);

        $replies = $comment->replies()
            ->with(self::AUTHOR_RELATIONS)
            ->oldest()
            ->paginate(10);

        return $this->attachPost($replies, $post);
    }

    public function storeComment(Account $account, Post $post, string $content, ?Comment $parent = null): Comment
    {
        if (! $post->isPublished()) {
            throw new PostNotPublishedException;
        }

        if ($parent) {
            $this->ensureBelongsToPost($post, $parent);

            // مستوى واحد من الردود: الرد على رد يُلحق بالتعليق الأصلي
            $parent = $parent->parent_id ? $parent->parent : $parent;
        }

        $comment = DB::transaction(function () use ($account, $post, $content, $parent) {
            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $account->profile()->id,
                'parent_id' => $parent?->id,
                'content' => $content,
            ]);

            $post->increment('comments_count');

            return $comment;
        });

        $comment->load(self::AUTHOR_RELATIONS)->setRelation('post', $post);

        if ($parent) {
            $this->notificationService->notifyCommentReply($comment, $parent->loadMissing('user.account'));
        } else {
            $this->notificationService->notifyPostInteraction($post, $account, 'commented', $comment);
        }

        return $comment;
    }

    public function deleteComment(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $repliesCount = $comment->replies()->count();

            $comment->replies()->delete();
            $comment->delete();

            // العدّاد لا ينزل تحت الصفر حتى لو كانت البيانات القديمة غير متطابقة
            $comment->post?->decrementCounter('comments_count', 1 + $repliesCount);
        });
    }

    private function ensureBelongsToPost(Post $post, Comment $comment): void
    {
        if ((int) $comment->post_id !== (int) $post->id) {
            throw new ApiException('التعليق لا يتبع لهذا المنشور.', 404, 'not_found');
        }
    }

    /**
     * كل التعليقات تتبع نفس المنشور، فنربطه مرة واحدة بدل استعلام لكل تعليق (تحتاجه الـ Policy)
     */
    private function attachPost(LengthAwarePaginator $comments, Post $post): LengthAwarePaginator
    {
        $comments->getCollection()->each(fn (Comment $comment) => $comment->setRelation('post', $post));

        return $comments;
    }
}
