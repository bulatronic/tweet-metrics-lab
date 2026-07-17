<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\API\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SearchTweetsQueryDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 280)]
        public string $q,

        #[Assert\Range(min: 0, max: 10_000)]
        public int $from = 0,

        #[Assert\Range(min: 1, max: 50)]
        public int $size = 20,
    ) {
    }
}
