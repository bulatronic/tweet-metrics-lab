<?php

declare(strict_types=1);

namespace App\Like\Domain\Entity;

use App\Like\Domain\ValueObject\LikeId;
use App\Tweet\Domain\ValueObject\TweetId;
use App\User\Domain\ValueObject\UserId;

final readonly class Like
{
    private function __construct(
        private LikeId $id,
        private TweetId $tweetId,
        private UserId $userId,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(TweetId $tweetId, UserId $userId): self
    {
        return new self(
            LikeId::generate(),
            $tweetId,
            $userId,
            new \DateTimeImmutable(),
        );
    }

    public static function reconstitute(
        LikeId $id,
        TweetId $tweetId,
        UserId $userId,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $tweetId, $userId, $createdAt);
    }

    public function id(): LikeId
    {
        return $this->id;
    }

    public function tweetId(): TweetId
    {
        return $this->tweetId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
