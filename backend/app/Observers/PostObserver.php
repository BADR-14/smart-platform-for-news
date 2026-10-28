<?php

namespace App\Observers;

use App\Enums\Post\PostStatus;
use App\Models\Company;
use App\Models\Post;

/**
 * يُبقي عدّاد منشورات الشركة (المنشورة فقط) وتاريخ آخر نشاط متوافقين مع الواقع
 */
class PostObserver
{
    public function created(Post $post): void
    {
        $this->syncCompany($post, touchActivity: true);
    }

    public function updated(Post $post): void
    {
        if ($post->wasChanged('status')) {
            $this->syncCompany($post);
        }
    }

    public function deleted(Post $post): void
    {
        $this->syncCompany($post);
    }

    public function restored(Post $post): void
    {
        $this->syncCompany($post);
    }

    private function syncCompany(Post $post, bool $touchActivity = false): void
    {
        $company = Company::where('account_id', $post->account_id)->first();

        if (! $company) {
            return;
        }

        $data = [
            'posts_count' => Post::where('account_id', $post->account_id)
                ->where('status', PostStatus::Published->value)
                ->count(),
        ];

        if ($touchActivity) {
            $data['last_active_at'] = now();
        }

        $company->update($data);
    }
}
