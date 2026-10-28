<?php

namespace App\Exceptions;

class UnauthorizedException extends ApiException
{
    protected $message = 'غير مصرح لك بتنفيذ هذا الإجراء.';

    protected int $status = 403;

    protected ?string $errorCode = 'forbidden';
}
