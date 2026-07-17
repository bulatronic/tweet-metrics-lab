<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\User\Domain\Exception\InvalidUsernameException;

final readonly class Username
{
    private const int MIN_LENGTH = 3;
    private const int MAX_LENGTH = 30;

    /**
     * @throws InvalidUsernameException
     */
    private function __construct(
        private string $value,
    ) {
        $length = mb_strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidUsernameException($value);
        }

        if (1 !== preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            throw new InvalidUsernameException($value);
        }
    }

    /**
     * @throws InvalidUsernameException
     */
    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
