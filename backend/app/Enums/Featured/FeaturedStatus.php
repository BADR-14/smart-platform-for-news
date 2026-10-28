<?php

namespace App\Enums\Featured;

enum FeaturedStatus: string
{
    case Queued = 'queued';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
