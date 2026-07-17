<?php

declare(strict_types=1);

namespace App\Tweet\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class TweetTextTooLongException extends DomainException
{
    public function __construct(int $length)
    {
        parent::__construct(sprintf(
            'Tweet text must be between 1 and 280 characters, got %d.',
            $length,
        ));
    }
}
