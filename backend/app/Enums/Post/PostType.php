<?php

namespace App\Enums\Post;

enum PostType: string
{
    case Lecture = 'lecture';
    case Exhibition = 'exhibition';
    case Event = 'event';
    case Course = 'course';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
