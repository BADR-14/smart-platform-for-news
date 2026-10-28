<?php

namespace App\Services;

use App\Enums\React\ReactAction;
use App\Exceptions\PostNotPublishedException;
use App\Models\Account;
use App\Models\Post;
use App\Models\UserReact;
use Illuminate\Support\Facades\DB;

class ReactService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * نفس التفاعل مرة ثانية يلغيه، وتفاعل مختلف يستبدله.
     *
     * @return array{action: string, react_type_id: int|null}
     */
    public function toggleReact(Post $post, Account $account, int $reactTypeId): array
    {
        if (! $post->isPublished()) {
            throw new PostNotPublishedException;
        }

        $userId = $account->profile()->id;

        $result = DB::transaction(function () use ($post, $userId, $reactTypeId) {
            $react = UserReact::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $react) {
                UserReact::create([
                    'post_id' => $post->id,
                    'user_id' => $userId,
                    'react_type_id' => $reactTypeId,
                ]);
                $post->increment('likes_count');

                return ['action' => ReactAction::Create->value, 'react_type_id' => $reactTypeId];
            }

            if ($react->react_type_id === $reactTypeId) {
                $react->delete();
                $post->decrementCounter('likes_count');

                return ['action' => ReactAction::Delete->value, 'react_type_id' => null];
            }

            $react->update(['react_type_id' => $reactTypeId]);

            return ['action' => ReactAction::Update->value, 'react_type_id' => $reactTypeId];
        });

        if ($result['action'] === ReactAction::Create->value) {
            $this->notificationService->notifyPostInteraction($post, $account, 'reacted');
        }

        return $result;
    }
}
