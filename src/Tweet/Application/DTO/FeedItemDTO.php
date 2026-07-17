<?php

declare(strict_types=1);

namespace App\Tweet\Application\DTO;

final readonly class FeedItemDTO
{
    public function __construct(
        public string $id,
        public string $tweetId,
        public string $authorId,
        public string $authorUsername,
        public string $text,
        public int $likesCount,
        public string $createdAt,
    ) {
    }
}
