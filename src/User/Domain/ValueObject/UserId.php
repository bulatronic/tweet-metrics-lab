<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\Shared\Domain\ValueObject\FromStringableValueObject;
use App\Shared\Domain\ValueObject\Uuid;
use Random\RandomException;

final readonly class UserId implements FromStringableValueObject
{
    private function __construct(
        private Uuid $uuid,
    ) {
    }

    /**
     * @throws InvalidUuidException
     * @throws RandomException
     */
    public static function generate(): self
    {
        return new self(Uuid::generate());
    }

    /**
     * @throws InvalidUuidException
     */
    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }

    public function toString(): string
    {
        return $this->uuid->toString();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
