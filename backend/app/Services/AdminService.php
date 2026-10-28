<?php

namespace App\Services;

use App\Enums\Account\AccountRole;
use App\Enums\Account\VerificationStatus;
use App\Enums\Post\PostStatus;
use App\Enums\Report\ReportStatus;
use App\Exceptions\ApiException;
use App\Models\Account;
use App\Models\Company;
use App\Models\FeaturedPost;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Report;
use App\Models\VerificationRequest;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    // ==================== Overview ====================

    /**
     * @return array<string, int|float>
     */
    public function overviewStats(): array
    {
        return [
            'users' => Account::where('account_type_id', AccountRole::User->value)->count(),
            'companies' => Company::verified()->count(),
            'posts' => Post::count(),
            'published_posts' => Post::published()->count(),
            'under_review_posts' => Post::where('status', PostStatus::UnderReview->value)->count(),
            'pending_reports' => Report::where('status', ReportStatus::Pending->value)->count(),
            'pending_verifications' => VerificationRequest::where('status', VerificationStatus::Pending->value)->count(),
            'plans' => Plan::where('is_active', true)->count(),
            'featured_posts' => FeaturedPost::running()->count(),
            'wallets_balance' => round((float) Wallet::sum('balance'), 2),
        ];
    }

    // ==================== Users ====================

    /**
     * @param  array{search?: string|null, status?: string|null, verification?: string|null}  $filters
     */
    public function getAllUsers(array $filters = []): LengthAwarePaginator
    {
        return Account::query()
            ->where('account_type_id', AccountRole::User->value)
            ->with(['accountType', 'user'])
            ->withCount('posts')
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where(fn (Builder $w) => $w
                ->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'blocked', fn (Builder $q) => $q->where('is_active', false))
            ->when($filters['verification'] ?? null, fn (Builder $q, $status) => $q->where('verification_status', $status))
            ->latest('id')
            ->paginate(10);
    }

    /**
     * @return array<string, int>
     */
    public function getUserStats(): array
    {
        $users = Account::where('account_type_id', AccountRole::User->value);

        return [
            'total' => (clone $users)->count(),
            'verified' => (clone $users)->where('verification_status', VerificationStatus::Approved->value)->count(),
            'blocked' => (clone $users)->where('is_active', false)->count(),
        ];
    }

    /**
     * حذف مستخدم من قبل الأدمن: يُخفى الحساب ومنشوراته وتُنهى جلساته
     */
    public function deleteAccount(Account $account): void
    {
        $this->ensureNotAdmin($account);

        DB::transaction(function () use ($account) {
            $account->posts()->delete();
            $account->tokens()->delete();
            $account->forceFill([
                'is_active' => false,
                'email' => "deleted-{$account->id}-{$account->email}",
            ])->save();
            $account->delete();
        });
    }

    // ==================== Companies ====================

    /**
     * @return array<string, int|float>
     */
    public function getCompanyStats(): array
    {
        return [
            'total_companies' => Company::verified()->count(),
            'blocked_companies' => Company::verified()->whereHas('account', fn (Builder $q) => $q->where('is_active', false))->count(),
            'total_balance' => round((float) Wallet::sum('balance'), 2),
        ];
    }

    /**
     * @param  array{search?: string|null, category_id?: int|string|null, status?: string|null}  $filters
     */
    public function getAllCompanies(array $filters = []): LengthAwarePaginator
    {
        return Company::query()
            ->verified()
            ->with(['account', 'wallet', 'category', 'activePlan.plan'])
            ->when($filters['search'] ?? null, fn (Builder $q, $search) => $q->where('companies.name', 'LIKE', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $id) => $q->where('category_id', $id))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $q) => $q->active())
            ->when(($filters['status'] ?? null) === 'blocked', fn (Builder $q) => $q->whereHas('account', fn (Builder $a) => $a->where('is_active', false)))
            ->latest('id')
            ->paginate(10);
    }

    // ==================== Ban / Unban ====================

    public function banAccount(Account $account, string $reason): void
    {
        $this->ensureNotAdmin($account);

        if (! $account->is_active) {
            throw new ApiException('هذا الحساب محظور مسبقاً.', 422, 'already_banned');
        }

        $account->update(['is_active' => false, 'ban_reason' => $reason]);
        $account->tokens()->delete();

        $this->notificationService->notifyAccountBanned($account, $reason);
    }

    public function unbanAccount(Account $account): void
    {
        if ($account->is_active) {
            throw new ApiException('هذا الحساب غير محظور.', 422, 'not_banned');
        }

        $account->update(['is_active' => true, 'ban_reason' => null]);
    }

    private function ensureNotAdmin(Account $account): void
    {
        if ($account->isAdmin()) {
            throw new ApiException('لا يمكن تنفيذ هذا الإجراء على حساب مدير.', 403, 'forbidden');
        }
    }
}
