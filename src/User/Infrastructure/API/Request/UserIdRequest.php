<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Request;

use ApiKit\Validator\Constraint\EntityExists;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserIdRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[EntityExists(DoctrineUser::class)]
        public string $id,
    ) {
    }
}
