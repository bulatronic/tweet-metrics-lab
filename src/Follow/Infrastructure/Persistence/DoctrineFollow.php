<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'follows')]
#[ORM\UniqueConstraint(name: 'uniq_follows_follower_followee', columns: ['follower_id', 'followee_id'])]
#[ORM\Index(name: 'idx_follows_follower_id', columns: ['follower_id'])]
#[ORM\Index(name: 'idx_follows_followee_id', columns: ['followee_id'])]
class DoctrineFollow
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'follower_id', type: 'uuid')]
    private Uuid $followerId;

    #[ORM\Column(name: 'followee_id', type: 'uuid')]
    private Uuid $followeeId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        Uuid $followerId,
        Uuid $followeeId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->followerId = $followerId;
        $this->followeeId = $followeeId;
        $this->createdAt = $createdAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFollowerId(): Uuid
    {
        return $this->followerId;
    }

    public function getFolloweeId(): Uuid
    {
        return $this->followeeId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
