<?php

declare(strict_types=1);

namespace App\Follow\Domain\Entity;

use App\Follow\Domain\Exception\CannotFollowYourselfException;
use App\Follow\Domain\ValueObject\FollowId;
use App\Shared\Domain\Exception\InvalidUuidException;
use App\User\Domain\ValueObject\UserId;
use Random\RandomException;

final readonly class Follow
{
    private function __construct(
        private FollowId $id,
        private UserId $followerId,
        private UserId $followeeId,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @throws CannotFollowYourselfException
     * @throws RandomException
     * @throws InvalidUuidException
     */
    public static function create(UserId $followerId, UserId $followeeId): self
    {
        if ($followerId->equals($followeeId)) {
            throw new CannotFollowYourselfException();
        }

        return new self(
            FollowId::generate(),
            $followerId,
            $followeeId,
            new \DateTimeImmutable(),
        );
    }

    public static function reconstitute(
        FollowId $id,
        UserId $followerId,
        UserId $followeeId,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $followerId, $followeeId, $createdAt);
    }

    public function id(): FollowId
    {
        return $this->id;
    }

    public function followerId(): UserId
    {
        return $this->followerId;
    }

    public function followeeId(): UserId
    {
        return $this->followeeId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
