<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeedService
{
    /**
     * منشورات الشركات التي يتابعها الحساب
     */
    public function getFollowedCompaniesPosts(Account $account): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->forResource($account)
            ->whereIn('account_id', function ($query) use ($account) {
                $query->select('companies.account_id')
                    ->from('follows')
                    ->join('companies', 'companies.id', '=', 'follows.company_id')
                    ->where('follows.account_id', $account->id);
            })
            ->latest('posts.created_at')
            ->paginate(10);
    }
}
