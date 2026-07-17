<?php

declare(strict_types=1);

namespace App\Tweet\Application\Query;

final readonly class GetUserTweetsQuery
{
    public function __construct(
        public string $userId,
        public ?string $cursor = null,
        public int $limit = 20,
    ) {
    }
}
