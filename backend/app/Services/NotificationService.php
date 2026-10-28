<?php

namespace App\Services;

use App\Enums\Account\AccountRole;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Post;
use App\Models\Report;
use App\Models\Review;
use App\Models\VerificationRequest;
use App\Notifications\AccountBannedNotification;
use App\Notifications\AuthActivityNotification;
use App\Notifications\CommentReplyNotification;
use App\Notifications\NewCompanyRegisteredNotification;
use App\Notifications\NewFollowerNotification;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PostDeletedNotification;
use App\Notifications\PostInteractionNotification;
use App\Notifications\PostModeratedNotification;
use App\Notifications\PostReviewedNotification;
use App\Notifications\ReportCreatedNotification;
use App\Notifications\VerificationRequestedNotification;
use App\Notifications\VerificationReviewedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\Notification as NotificationContract;

class NotificationService
{
    // ==================== إشعارات المستخدمين ====================

    public function notifyCommentReply(Comment $reply, Comment $parentComment): void
    {
        $parentOwner = $parentComment->user;

        // لا إشعار إذا رد المستخدم على تعليقه
        if (! $parentOwner || $parentOwner->id === $reply->user_id) {
            return;
        }

        $parentOwner->account?->notify(new CommentReplyNotification($reply, $parentComment));
    }

    public function notifyVerificationReviewed(VerificationRequest $verificationRequest): void
    {
        $verificationRequest->account?->notify(new VerificationReviewedNotification($verificationRequest));
    }

    public function notifyPostReviewed(Review $review): void
    {
        $post = $review->post;

        if (! $post || $post->account_id === $review->user?->account_id) {
            return;
        }

        $post->account?->notify(new PostReviewedNotification($review));
    }

    public function notifyPostInteraction(Post $post, Account $actorAccount, string $action, ?Comment $comment = null): void
    {
        // لا إشعار إذا تفاعل صاحب المنشور مع منشوره
        if ($post->isOwnedBy($actorAccount)) {
            return;
        }

        $post->account?->notify(new PostInteractionNotification($post, $actorAccount, $action, $comment));
    }

    public function notifyAccountBanned(Account $account, string $reason): void
    {
        $account->notify(new AccountBannedNotification($reason));
    }

    public function notifyPostDeleted(Account $ownerAccount, int $postId, string $postTitle): void
    {
        $ownerAccount->notify(new PostDeletedNotification($postId, $postTitle));
    }

    public function notifyPostModerated(Post $post): void
    {
        $post->account?->notify(new PostModeratedNotification($post));
    }

    /**
     * @param  'register'|'login'  $action
     */
    public function notifyAuthActivity(Account $account, string $action): void
    {
        // إشعار تسجيل الدخول يحترم خيار "إشعارات تسجيل الدخول" في إعدادات الحساب
        if ($action === 'login' && ! ($account->notificationPreference?->login_notifications ?? true)) {
            return;
        }

        $account->notify(new AuthActivityNotification($action));
    }

    public function notifyNewFollower(Company $company, Account $followerAccount): void
    {
        if ((int) $company->account_id === (int) $followerAccount->id) {
            return;
        }

        $company->account?->notify(new NewFollowerNotification($followerAccount, $company));
    }

    // ==================== إشعارات لوحة الأدمن ====================

    public function notifyNewUserRegistered(Account $account): void
    {
        $this->notifyAllAdmins(new NewUserRegisteredNotification($account));
    }

    public function notifyNewCompanyRegistered(Company $company): void
    {
        $this->notifyAllAdmins(new NewCompanyRegisteredNotification($company));
    }

    public function notifyVerificationRequested(VerificationRequest $verificationRequest): void
    {
        $this->notifyAllAdmins(new VerificationRequestedNotification($verificationRequest->loadMissing('account')));
    }

    public function notifyReportCreated(Report $report, Account $reporter, string $reportedLabel): void
    {
        $this->notifyAllAdmins(new ReportCreatedNotification($report, $reporter, $reportedLabel));
    }

    private function notifyAllAdmins(NotificationContract $notification): void
    {
        Account::where('account_type_id', AccountRole::Admin->value)
            ->where('is_active', true)
            ->with('notificationPreference')
            ->get()
            ->each(fn (Account $admin) => $admin->notify($notification));
    }

    // ==================== قراءة وإدارة الإشعارات ====================

    public function getNotifications(Account $account, bool $unreadOnly = false, int $perPage = 15): LengthAwarePaginator
    {
        return $account->notifications()
            ->when($unreadOnly, fn ($q) => $q->unread())
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCount(Account $account): int
    {
        return $account->notifications()->unread()->count();
    }

    public function markAsRead(Account $account, string $notificationId): void
    {
        $account->notifications()->findOrFail($notificationId)->markAsRead();
    }

    public function markAllAsRead(Account $account): void
    {
        $account->notifications()->unread()->update(['read_at' => now()]);
    }

    public function delete(Account $account, string $notificationId): void
    {
        $account->notifications()->findOrFail($notificationId)->delete();
    }
}
