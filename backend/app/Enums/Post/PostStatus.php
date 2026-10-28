<?php

namespace App\Enums\Post;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case UnderReview = 'under_review';
    case Rejected = 'rejected';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
