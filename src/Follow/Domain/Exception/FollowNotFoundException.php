<?php

declare(strict_types=1);

namespace App\Follow\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\User\Domain\ValueObject\UserId;

final class FollowNotFoundException extends DomainException
{
    public function __construct(UserId $followerId, UserId $followeeId)
    {
        parent::__construct(sprintf(
            'Follow relation not found for "%s" -> "%s".',
            $followerId->toString(),
            $followeeId->toString(),
        ));
    }
}
