<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessException extends HttpException
{
    protected string $errorCode;

    public function __construct(int $statusCode, string $errorCode, string $message)
    {
        parent::__construct($statusCode, $message);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
