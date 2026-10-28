<?php

namespace App\Services;

use App\Enums\Post\PostStatus;
use App\Models\Account;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * يحمّل كل ما تحتاجه ProfileResource / PublicProfileResource
     */
    public function getProfile(Account $account): Account
    {
        $account->profile();

        return $account
            ->load([
                'accountType',
                'company.category',
                'user' => fn ($q) => $q->withCount(['reviews', 'reacts', 'bookmarks']),
            ])
            ->loadCount([
                'posts as published_posts_count' => fn ($q) => $q->where('status', PostStatus::Published->value),
                'followedCompanies as following_count',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Account $account, array $data): Account
    {
        DB::transaction(function () use ($account, $data) {
            if (array_key_exists('name', $data) && $data['name'] !== null) {
                $account->update(['name' => $data['name']]);
            }

            $account->profile()->update(
                array_intersect_key($data, array_flip(['phone_number', 'linkedin_url', 'short_bio']))
            );
        });

        return $this->getProfile($account->refresh());
    }

    public function updateAvatar(Account $account, UploadedFile $file): Account
    {
        $oldAvatar = $account->avatar;

        $account->update(['avatar' => $file->store('avatars', 'public')]);

        // شعار الشركة يشارك نفس الملف مع صورة الحساب بعد الموافقة، فلا نحذفه إن كان مستخدماً
        if ($oldAvatar && $oldAvatar !== $account->company?->logo_url) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $account;
    }

    /**
     * التقييمات التي كتبها صاحب الحساب
     */
    public function getReviews(Account $account): LengthAwarePaginator
    {
        return $account->profile()->reviews()
            ->with('post.category')
            ->latest()
            ->paginate(10);
    }
}
