<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\User\Domain\ValueObject\PasswordHash;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): PasswordHash;

    public function verify(PasswordHash $hash, string $plainPassword): bool;
}
