<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Request;

use ApiKit\Validator\Constraint\EntityExists;
use App\Tweet\Infrastructure\Persistence\DoctrineTweet;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TweetIdRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[EntityExists(DoctrineTweet::class)]
        public string $id,
    ) {
    }
}
