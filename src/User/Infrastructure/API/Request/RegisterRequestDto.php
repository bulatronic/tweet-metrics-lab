<?php

declare(strict_types=1);

namespace App\User\Infrastructure\API\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 30)]
        #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+$/')]
        public string $username,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 72)]
        public string $password,
    ) {
    }
}
