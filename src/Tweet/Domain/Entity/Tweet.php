<?php

declare(strict_types=1);

namespace App\Tweet\Domain\Entity;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Domain\Exception\CannotDecrementLikesException;
use App\Tweet\Domain\ValueObject\TweetId;
use App\Tweet\Domain\ValueObject\TweetText;
use App\User\Domain\ValueObject\UserId;
use Random\RandomException;

final class Tweet
{
    private function __construct(
        private readonly TweetId $id,
        private readonly UserId $authorId,
        private readonly TweetText $text,
        private readonly \DateTimeImmutable $createdAt,
        private int $likesCount,
    ) {
    }

    /**
     * @throws RandomException
     * @throws InvalidUuidException
     */
    public static function create(UserId $authorId, TweetText $text): self
    {
        return new self(
            TweetId::generate(),
            $authorId,
            $text,
            new \DateTimeImmutable(),
            0,
        );
    }

    public static function reconstitute(
        TweetId $id,
        UserId $authorId,
        TweetText $text,
        \DateTimeImmutable $createdAt,
        int $likesCount,
    ): self {
        return new self($id, $authorId, $text, $createdAt, $likesCount);
    }

    public function incrementLikes(): void
    {
        ++$this->likesCount;
    }

    /**
     * @throws CannotDecrementLikesException
     */
    public function decrementLikes(): void
    {
        if ($this->likesCount <= 0) {
            throw new CannotDecrementLikesException();
        }

        --$this->likesCount;
    }

    public function id(): TweetId
    {
        return $this->id;
    }

    public function authorId(): UserId
    {
        return $this->authorId;
    }

    public function text(): TweetText
    {
        return $this->text;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function likesCount(): int
    {
        return $this->likesCount;
    }
}
