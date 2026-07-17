<?php

declare(strict_types=1);

namespace App\Like\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'likes')]
#[ORM\UniqueConstraint(name: 'uniq_likes_tweet_user', columns: ['tweet_id', 'user_id'])]
#[ORM\Index(name: 'idx_likes_tweet_id', columns: ['tweet_id'])]
#[ORM\Index(name: 'idx_likes_user_id', columns: ['user_id'])]
class DoctrineLike
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'tweet_id', type: 'uuid')]
    private Uuid $tweetId;

    #[ORM\Column(name: 'user_id', type: 'uuid')]
    private Uuid $userId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        Uuid $tweetId,
        Uuid $userId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->tweetId = $tweetId;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTweetId(): Uuid
    {
        return $this->tweetId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
