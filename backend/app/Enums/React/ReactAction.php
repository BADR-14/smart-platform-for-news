<?php

namespace App\Enums\React;

enum ReactAction: string
{
    case Create = 'created';
    case Update = 'updated';
    case Delete = 'deleted';
}
