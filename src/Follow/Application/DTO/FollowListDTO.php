<?php

declare(strict_types=1);

namespace App\Follow\Application\DTO;

final readonly class FollowListDTO
{
    /**
     * @param list<FollowUserDTO> $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {
    }
}
