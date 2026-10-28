<?php

namespace App\Services;

use App\Exceptions\AlreadyShareException;
use App\Exceptions\ApiException;
use App\Exceptions\PostNotPublishedException;
use App\Models\Account;
use App\Models\Post;
use App\Models\PostShare;
use Illuminate\Support\Facades\DB;

class PostShareService
{
    public function __construct(private readonly PostService $postService) {}

    public function store(?string $shareText, Account $account, Post $post): PostShare
    {
        if (! $post->isPublished()) {
            throw new PostNotPublishedException;
        }

        if ($post->isOwnedBy($account)) {
            throw new ApiException('لا يمكنك مشاركة منشورك.', 422, 'cannot_share_own_post');
        }

        if (PostShare::where('account_id', $account->id)->where('post_id', $post->id)->exists()) {
            throw new AlreadyShareException;
        }

        $share = DB::transaction(function () use ($shareText, $account, $post) {
            $share = PostShare::create([
                'account_id' => $account->id,
                'post_id' => $post->id,
                'share_text' => $shareText,
            ]);

            $post->increment('shares_count');

            return $share;
        });

        return $this->loadForResource($share, $account);
    }

    public function updateSharePost(PostShare $postShare, Account $account, ?string $shareText): PostShare
    {
        $postShare->update(['share_text' => $shareText]);

        return $this->loadForResource($postShare, $account);
    }

    public function destroySharePost(PostShare $postShare): void
    {
        DB::transaction(function () use ($postShare) {
            $post = $postShare->post;

            $postShare->delete();

            $post?->decrementCounter('shares_count');
        });
    }

    private function loadForResource(PostShare $share, Account $viewer): PostShare
    {
        $share->load(['account.accountType', 'account.company']);

        return $share->setRelation('post', $this->postService->getPost($share->post()->firstOrFail(), $viewer));
    }
}
