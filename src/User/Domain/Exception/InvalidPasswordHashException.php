<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidPasswordHashException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Password hash must not be empty.');
    }
}
