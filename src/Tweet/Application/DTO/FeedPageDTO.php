<?php

declare(strict_types=1);

namespace App\Tweet\Application\DTO;

final readonly class FeedPageDTO
{
    /**
     * @param list<FeedItemDTO> $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {
    }
}
