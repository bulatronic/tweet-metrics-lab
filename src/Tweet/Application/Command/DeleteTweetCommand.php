<?php

declare(strict_types=1);

namespace App\Tweet\Application\Command;

final readonly class DeleteTweetCommand
{
    public function __construct(
        public string $tweetId,
    ) {
    }
}
