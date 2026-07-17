<?php

declare(strict_types=1);

namespace App\Like\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;

final class LikeAlreadyExistsException extends DomainException
{
    public function __construct(TweetId $tweetId, UserId $userId)
    {
        parent::__construct(sprintf(
            'User "%s" already liked tweet "%s".',
            $userId->toString(),
            $tweetId->toString(),
        ));
    }
}
