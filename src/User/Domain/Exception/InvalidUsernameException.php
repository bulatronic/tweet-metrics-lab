<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidUsernameException extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('Invalid username: "%s".', $value));
    }
}
