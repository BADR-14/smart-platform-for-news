<?php

namespace App\Enums\Report;

/**
 * الإجراء الذي يتخذه الأدمن عند معالجة البلاغ
 */
enum ReportAction: string
{
    case None = 'none';

    // بلاغ على منشور
    case UnderReview = 'under_review';
    case RejectPost = 'reject_post';
    case DeletePost = 'delete_post';

    // بلاغ على حساب
    case BanAccount = 'ban_account';

    /**
     * @return list<string>
     */
    public static function forType(string $reportableType): array
    {
        return match ($reportableType) {
            'post' => [self::None->value, self::UnderReview->value, self::RejectPost->value, self::DeletePost->value],
            'account' => [self::None->value, self::BanAccount->value],
            default => [self::None->value],
        };
    }
}
