<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidEmailException extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('Invalid email address: "%s".', $value));
    }
}
