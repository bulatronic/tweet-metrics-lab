<?php

declare(strict_types=1);

namespace App\Follow\Application\Command;

final readonly class UnfollowUserCommand
{
    public function __construct(
        public string $followerId,
        public string $followeeId,
    ) {
    }
}
