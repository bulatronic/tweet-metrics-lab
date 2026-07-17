<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Flat read-model projection for the feed (no FKs, denormalized authorUsername).
 */
#[ORM\Entity]
#[ORM\Table(name: 'feed_items')]
#[ORM\UniqueConstraint(name: 'uniq_feed_items_tweet_id', columns: ['tweet_id'])]
#[ORM\Index(name: 'idx_feed_items_created_at_id', columns: ['created_at', 'id'])]
class DoctrineFeedItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'tweet_id', type: 'uuid')]
    private Uuid $tweetId;

    #[ORM\Column(name: 'author_id', type: 'uuid')]
    private Uuid $authorId;

    #[ORM\Column(name: 'author_username', length: 30)]
    private string $authorUsername;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(name: 'likes_count', type: 'integer', options: ['default' => 0])]
    private int $likesCount;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        Uuid $tweetId,
        Uuid $authorId,
        string $authorUsername,
        string $text,
        int $likesCount,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->tweetId = $tweetId;
        $this->authorId = $authorId;
        $this->authorUsername = $authorUsername;
        $this->text = $text;
        $this->likesCount = $likesCount;
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

    public function getAuthorId(): Uuid
    {
        return $this->authorId;
    }

    public function getAuthorUsername(): string
    {
        return $this->authorUsername;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
