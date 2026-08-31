<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Request;

use ApiKit\Validator\Constraint\EntityExists;
use App\Tweet\Infrastructure\Persistence\DoctrineTweet;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TweetIdRequest
{
    public function __construct(
        // Sequential: do not hit the database until the value is a well-formed uuid.
        #[Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Uuid(),
            new EntityExists(DoctrineTweet::class),
        ])]
        public string $id,
    ) {
    }
}
