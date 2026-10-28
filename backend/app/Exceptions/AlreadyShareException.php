<?php

namespace App\Exceptions;

class AlreadyShareException extends ApiException
{
    protected $message = 'لقد قمت بمشاركة هذا المنشور مسبقاً.';

    protected int $status = 409;

    protected ?string $errorCode = 'already_shared';
}
