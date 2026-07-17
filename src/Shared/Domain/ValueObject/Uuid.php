<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidUuidException;
use Random\RandomException;

/**
 * Immutable UUID wrapper (stdlib only — no Symfony/Ramsey).
 * New IDs are generated as UUID v7 (time-ordered).
 */
final readonly class Uuid implements FromStringableValueObject
{
    /**
     * @throws InvalidUuidException
     */
    private function __construct(
        private string $value,
    ) {
        if (1 !== preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        )) {
            throw new InvalidUuidException($value);
        }
    }

    /**
     * @throws InvalidUuidException
     */
    public static function fromString(string $value): self
    {
        return new self(strtolower($value));
    }

    /**
     * @throws InvalidUuidException
     * @throws RandomException
     */
    public static function generate(): self
    {
        $unixTsMs = (int) round(microtime(true) * 1000);

        // 48-bit big-endian timestamp: take the lower 6 bytes of a 64-bit pack
        $timeBytes = substr(pack('J', $unixTsMs), 2, 6);

        $randBytes = random_bytes(10);
        $randBytes[0] = chr((ord($randBytes[0]) & 0x0F) | 0x70); // version 7
        $randBytes[2] = chr((ord($randBytes[2]) & 0x3F) | 0x80); // RFC 4122 variant

        $hex = bin2hex($timeBytes.$randBytes);

        return new self(sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        ));
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
