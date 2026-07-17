<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTweetRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 280)]
        public string $text,
    ) {
    }
}
