<?php

declare(strict_types=1);

namespace Bluehost\VerifactiApi\Exception;

class ValidationException extends VerifactiException
{
    /**
     * @var array<int, string>
     */
    private array $errors;

    /**
     * @param array<int, string> $errors
     */
    public function __construct(string $message = 'The request payload is invalid.', array $errors = array(), int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(self::buildMessage($message, $errors), $code, $previous);
        $this->errors = $errors;
    }

    /**
     * @param array<int, string> $errors
     */
    public static function buildMessage(string $message, array $errors): string
    {
        if ($errors === array()) {
            return $message;
        }

        return $message . ': ' . implode('; ', $errors);
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
