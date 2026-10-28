<?php

namespace App\Exceptions;

class AlreadyReportedException extends ApiException
{
    protected $message = 'لقد قمت بالإبلاغ عن هذا المحتوى مسبقاً.';

    protected int $status = 409;

    protected ?string $errorCode = 'already_reported';
}
