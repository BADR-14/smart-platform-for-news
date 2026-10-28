<?php

namespace App\Exceptions;

class PostNotPublishedException extends ApiException
{
    protected $message = 'لا يمكن تنفيذ هذا الإجراء على منشور غير منشور.';

    protected int $status = 422;

    protected ?string $errorCode = 'post_not_published';
}
