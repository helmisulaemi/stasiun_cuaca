<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessException extends HttpException
{
    protected string $errorCode;

    protected array $details;

    public function __construct(int $statusCode, string $errorCode, string $message, array $details = [])
    {
        parent::__construct($statusCode, $message);
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
