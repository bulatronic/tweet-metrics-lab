<?php

declare(strict_types=1);

namespace App\Follow\Domain\Repository;

use App\Follow\Domain\Entity\Follow;
use App\Follow\Domain\ValueObject\FollowId;
use App\User\Domain\ValueObject\UserId;

interface FollowRepositoryInterface
{
    public function save(Follow $follow): void;

    public function remove(Follow $follow): void;

    public function findById(FollowId $id): ?Follow;

    public function findByFollowerAndFollowee(UserId $followerId, UserId $followeeId): ?Follow;
}
