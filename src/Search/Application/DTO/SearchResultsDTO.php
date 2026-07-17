<?php

declare(strict_types=1);

namespace App\Search\Application\DTO;

final readonly class SearchResultsDTO
{
    /**
     * @param list<SearchHitDTO> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $from,
        public int $size,
    ) {
    }
}
