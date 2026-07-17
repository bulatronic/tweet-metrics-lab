<?php

declare(strict_types=1);

namespace App\Like\Application\Command;

final readonly class UnlikeTweetCommand
{
    public function __construct(
        public string $tweetId,
        public string $userId,
    ) {
    }
}
