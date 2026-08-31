<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Request;

use ApiKit\Validator\Constraint\EntityExists;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserIdRequest
{
    public function __construct(
        // Sequential: do not hit the database until the value is a well-formed uuid.
        #[Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Uuid(),
            new EntityExists(DoctrineUser::class),
        ])]
        public string $id,
    ) {
    }
}
