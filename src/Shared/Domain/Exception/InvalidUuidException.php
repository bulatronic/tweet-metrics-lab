<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class InvalidUuidException extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf('Invalid UUID: "%s".', $value));
    }
}
