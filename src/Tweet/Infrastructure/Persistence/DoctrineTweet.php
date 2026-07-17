<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tweets')]
#[ORM\Index(name: 'idx_tweets_author_id', columns: ['author_id'])]
class DoctrineTweet
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'author_id', type: 'uuid')]
    private Uuid $authorId;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'likes_count', type: 'integer', options: ['default' => 0])]
    private int $likesCount;

    public function __construct(
        Uuid $id,
        Uuid $authorId,
        string $text,
        \DateTimeImmutable $createdAt,
        int $likesCount,
    ) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->text = $text;
        $this->createdAt = $createdAt;
        $this->likesCount = $likesCount;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAuthorId(): Uuid
    {
        return $this->authorId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function sync(string $text, int $likesCount): void
    {
        $this->text = $text;
        $this->likesCount = $likesCount;
    }
}
