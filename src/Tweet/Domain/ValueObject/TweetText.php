<?php

declare(strict_types=1);

namespace App\Tweet\Domain\ValueObject;

use App\Shared\Domain\ValueObject\FromStringableValueObject;
use App\Tweet\Domain\Exception\TweetTextTooLongException;

final readonly class TweetText implements FromStringableValueObject
{
    private const int MAX_LENGTH = 280;

    /**
     * @throws TweetTextTooLongException
     */
    private function __construct(
        private string $value,
    ) {
        if ('' === $value) {
            throw new TweetTextTooLongException(0);
        }

        $length = mb_strlen($value);
        if ($length > self::MAX_LENGTH) {
            throw new TweetTextTooLongException($length);
        }
    }

    /**
     * @throws TweetTextTooLongException
     */
    public static function fromString(string $value): self
    {
        return new self(trim($value));
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
