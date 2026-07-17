<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class UserAlreadyExistsException extends DomainException
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('User already exists: "%s".', $identifier));
    }
}
