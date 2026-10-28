<?php

namespace App\Exceptions;

class InsufficientBalanceException extends ApiException
{
    protected $message = 'رصيد المحفظة غير كافٍ.';

    protected int $status = 422;

    protected ?string $errorCode = 'insufficient_balance';
}
