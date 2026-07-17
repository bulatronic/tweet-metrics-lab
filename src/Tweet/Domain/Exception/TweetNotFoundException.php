<?php

declare(strict_types=1);

namespace App\Tweet\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Tweet\Domain\ValueObject\TweetId;

final class TweetNotFoundException extends DomainException
{
    public function __construct(TweetId $id)
    {
        parent::__construct(sprintf('Tweet not found: "%s".', $id->toString()));
    }
}
