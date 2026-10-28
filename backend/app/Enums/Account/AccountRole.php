<?php

namespace App\Enums\Account;

/**
 * نوع الحساب — القيم مطابقة لمعرّفات جدول account_types المزروعة بالـ AccountTypeSeeder
 */
enum AccountRole: int
{
    case User = 1;
    case Company = 2;
    case Admin = 3;

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Company => 'Company',
            self::Admin => 'Admin',
        };
    }

    public function slug(): string
    {
        return strtolower($this->label());
    }
}
