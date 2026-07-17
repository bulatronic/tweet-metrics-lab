<?php

declare(strict_types=1);

namespace App\Search\Application\Query;

final readonly class GetSearchResultsQuery
{
    public function __construct(
        public string $q,
        public int $from = 0,
        public int $size = 20,
    ) {
    }
}
