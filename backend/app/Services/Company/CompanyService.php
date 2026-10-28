<?php

namespace App\Services\Company;

use App\Exceptions\ApiException;
use App\Models\Account;
use App\Models\Company;
use App\Models\Follow;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    // ==================== Queries ====================

    /**
     * قائمة الشركات الموثّقة والنشطة مع البحث والفلترة
     */
    public function getCompanies(?string $search, ?int $categoryId, ?Account $viewer = null, int $perPage = 12): LengthAwarePaginator
    {
        return $this->publicQuery($viewer)
            ->when($search, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('companies.name', 'LIKE', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('companies.description', 'LIKE', '%'.addcslashes($search, '%_\\').'%')))
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->orderByDesc('followers_count')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * صفحة الشركة العامة. الشركة غير الموثّقة يراها صاحبها فقط.
     */
    public function getCompany(Company $company, ?Account $viewer = null): Company
    {
        $isOwner = $viewer !== null && (int) $company->account_id === (int) $viewer->id;

        $found = ($isOwner || $viewer?->isAdmin()
            ? $this->baseQuery($viewer)
            : $this->publicQuery($viewer)
        )->find($company->id);

        if (! $found) {
            throw new ApiException('العنصر المطلوب غير موجود.', 404, 'not_found');
        }

        return $found;
    }

    public function getCompanyPosts(Company $company, ?Account $viewer = null, int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()
            ->where('account_id', $company->account_id)
            ->published()
            ->forResource($viewer)
            ->latest('posts.created_at')
            ->paginate($perPage);
    }

    // ==================== Follow ====================

    /**
     * @return array{is_following: bool, followers_count: int}
     */
    public function follow(Company $company, Account $account): array
    {
        if ((int) $company->account_id === (int) $account->id) {
            throw new ApiException('لا يمكنك متابعة شركتك.', 422, 'cannot_follow_own_company');
        }

        $follow = Follow::firstOrCreate([
            'company_id' => $company->id,
            'account_id' => $account->id,
        ]);

        if ($follow->wasRecentlyCreated) {
            $this->notificationService->notifyNewFollower($company, $account);
        }

        return ['is_following' => true, 'followers_count' => $this->syncFollowersCount($company)];
    }

    /**
     * @return array{is_following: bool, followers_count: int}
     */
    public function unfollow(Company $company, Account $account): array
    {
        Follow::where('company_id', $company->id)->where('account_id', $account->id)->delete();

        return ['is_following' => false, 'followers_count' => $this->syncFollowersCount($company)];
    }

    // ==================== Company profile (لوحة الشركة) ====================

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(Company $company, array $data, ?UploadedFile $logo = null): Company
    {
        DB::transaction(function () use ($company, $data, $logo) {
            $attributes = array_intersect_key($data, array_flip([
                'name', 'website_url', 'category_id', 'description', 'address', 'phone',
            ]));

            if ($logo) {
                $oldLogo = $company->logo_url;
                $attributes['logo_url'] = $logo->store('logos', 'public');

                if ($oldLogo) {
                    Storage::disk('public')->delete($oldLogo);
                }
            }

            $company->update($attributes);

            // اسم وشعار الشركة هما اسم وصورة الحساب الظاهران على المنشورات
            $company->account->update(array_filter([
                'name' => $attributes['name'] ?? null,
                'avatar' => $attributes['logo_url'] ?? null,
            ]));
        });

        return $company->refresh()->load(['account', 'category']);
    }

    // ==================== Internals ====================

    private function baseQuery(?Account $viewer): Builder
    {
        return Company::query()
            ->with(['account', 'category'])
            ->when($viewer, fn (Builder $q) => $q->withExists([
                'follows as is_following' => fn (Builder $f) => $f->where('account_id', $viewer->id),
            ]));
    }

    private function publicQuery(?Account $viewer): Builder
    {
        return $this->baseQuery($viewer)->verified()->active();
    }

    /**
     * العدّاد يُحسب دائماً من جدول follows حتى لا ينحرف عن الواقع
     */
    private function syncFollowersCount(Company $company): int
    {
        $count = $company->follows()->count();

        $company->update(['followers_count' => $count]);

        return $count;
    }
}
