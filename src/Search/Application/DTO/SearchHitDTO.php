<?php

declare(strict_types=1);

namespace App\Search\Application\DTO;

final readonly class SearchHitDTO
{
    public function __construct(
        public string $id,
        public string $text,
        public string $authorId,
        public string $authorUsername,
        public string $createdAt,
        public ?float $score,
    ) {
    }
}
