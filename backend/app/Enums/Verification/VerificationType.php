<?php

namespace App\Enums\Verification;

enum VerificationType: string
{
    case User = 'user';
    case Company = 'company';
}
