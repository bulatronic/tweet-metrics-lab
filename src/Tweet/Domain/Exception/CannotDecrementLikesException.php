<?php

declare(strict_types=1);

namespace App\Tweet\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class CannotDecrementLikesException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Likes count cannot be negative.');
    }
}
