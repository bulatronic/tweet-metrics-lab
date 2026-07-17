<?php

declare(strict_types=1);

namespace App\Follow\Application\DTO;

final readonly class FollowUserDTO
{
    public function __construct(
        public string $id,
        public string $username,
        public string $followedAt,
    ) {
    }
}
