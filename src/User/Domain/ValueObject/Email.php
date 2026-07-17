<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\User\Domain\Exception\InvalidEmailException;

final readonly class Email
{
    /**
     * @throws InvalidEmailException
     */
    private function __construct(
        private string $value,
    ) {
        if (false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException($value);
        }
    }

    /**
     * @throws InvalidEmailException
     */
    public static function fromString(string $value): self
    {
        return new self(strtolower(trim($value)));
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
