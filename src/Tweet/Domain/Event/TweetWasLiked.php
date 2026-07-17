<?php

declare(strict_types=1);

namespace App\Tweet\Domain\Event;

use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;

final readonly class TweetWasLiked
{
    public function __construct(
        public TweetId $tweetId,
        public UserId $userId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
