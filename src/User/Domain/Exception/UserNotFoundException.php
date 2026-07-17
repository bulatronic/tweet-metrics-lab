<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\User\Domain\ValueObject\UserId;

final class UserNotFoundException extends DomainException
{
    public function __construct(UserId $id)
    {
        parent::__construct(sprintf('User not found: "%s".', $id->toString()));
    }
}
