<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CursorQueryDto
{
    public function __construct(
        #[Assert\Length(max: 512)]
        public ?string $cursor = null,

        #[Assert\Range(min: 1, max: 50)]
        public int $limit = 20,
    ) {
    }
}
