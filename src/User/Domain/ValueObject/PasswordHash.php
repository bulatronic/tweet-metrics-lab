<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\FromStringableValueObject;
use App\User\Domain\Exception\InvalidPasswordHashException;

final readonly class PasswordHash implements FromStringableValueObject
{
    /**
     * @throws InvalidPasswordHashException
     */
    private function __construct(
        private string $value,
    ) {
        if ('' === $value) {
            throw new InvalidPasswordHashException();
        }
    }

    /**
     * @throws InvalidPasswordHashException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
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
