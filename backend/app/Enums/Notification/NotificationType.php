<?php

namespace App\Enums\Notification;

enum NotificationType: string
{
    case CommentReply = 'comment_reply';
    case VerificationReviewed = 'verification_reviewed';
    case PostReviewed = 'post_reviewed';
    case PostReacted = 'post_reacted';
    case PostCommented = 'post_commented';
    case AccountBanned = 'account_banned';
    case PostDeleted = 'post_deleted';
    case PostModerated = 'post_moderated';
    case AccountCreated = 'account_created';
    case LoginSuccess = 'login_success';
    case NewFollower = 'new_follower';

    // إشعارات لوحة تحكم الأدمن
    case NewUserRegistered = 'new_user_registered';
    case NewCompanyRegistered = 'new_company_registered';
    case ReportCreated = 'report_created';
    case VerificationRequested = 'verification_requested';
}
