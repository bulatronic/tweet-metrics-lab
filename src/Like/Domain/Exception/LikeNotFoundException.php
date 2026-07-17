<?php

declare(strict_types=1);

namespace App\Like\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;

final class LikeNotFoundException extends DomainException
{
    public function __construct(TweetId $tweetId, UserId $userId)
    {
        parent::__construct(sprintf(
            'Like not found for user "%s" and tweet "%s".',
            $userId->toString(),
            $tweetId->toString(),
        ));
    }
}
