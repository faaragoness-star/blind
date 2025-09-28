<?php

declare(strict_types=1);

namespace G3D\ModelsManager\Validation;

final class GlbValidationError
{
    private string $code;

    private string $message;

    public function __construct(string $code, string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
