<?php

declare(strict_types=1);

namespace App\Tweet\Application\DTO;

final readonly class TweetDTO
{
    public function __construct(
        public string $id,
        public string $authorId,
        public string $text,
        public int $likesCount,
        public string $createdAt,
    ) {
    }
}
