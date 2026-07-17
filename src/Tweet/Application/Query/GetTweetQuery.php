<?php

declare(strict_types=1);

namespace App\Tweet\Application\Query;

final readonly class GetTweetQuery
{
    public function __construct(
        public string $tweetId,
    ) {
    }
}
