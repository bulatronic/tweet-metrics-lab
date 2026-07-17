<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Contract for immutable string-backed value objects.
 *
 * Implementations narrow fromString() return type to self (valid covariance).
 */
interface FromStringableValueObject
{
    public static function fromString(string $value): FromStringableValueObject;

    public function toString(): string;

    public function __toString(): string;
}
