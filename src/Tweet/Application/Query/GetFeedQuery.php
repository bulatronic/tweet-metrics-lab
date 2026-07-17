<?php

declare(strict_types=1);

namespace App\Tweet\Application\Query;

final readonly class GetFeedQuery
{
    public function __construct(
        public ?string $cursor = null,
        public int $limit = 20,
    ) {
    }
}
