<?php

declare(strict_types=1);

namespace App\Follow\Application\Query;

final readonly class GetFollowingQuery
{
    public function __construct(
        public string $userId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {
    }
}
