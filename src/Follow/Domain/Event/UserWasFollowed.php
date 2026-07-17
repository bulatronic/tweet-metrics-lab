<?php

declare(strict_types=1);

namespace App\Follow\Domain\Event;

use App\User\Domain\ValueObject\UserId;

final readonly class UserWasFollowed
{
    public function __construct(
        public UserId $followerId,
        public UserId $followeeId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
