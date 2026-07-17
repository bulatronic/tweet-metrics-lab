<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class UserDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $username,
        public string $createdAt,
    ) {
    }
}
