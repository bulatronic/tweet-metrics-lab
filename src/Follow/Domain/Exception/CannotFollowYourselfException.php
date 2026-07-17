<?php

declare(strict_types=1);

namespace App\Follow\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class CannotFollowYourselfException extends DomainException
{
    public function __construct()
    {
        parent::__construct('A user cannot follow themselves.');
    }
}
