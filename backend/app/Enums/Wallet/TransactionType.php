<?php

namespace App\Enums\Wallet;

enum TransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Gift = 'gift';
}
