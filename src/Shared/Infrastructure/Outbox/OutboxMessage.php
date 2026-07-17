<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_messages')]
#[ORM\Index(name: 'idx_outbox_messages_published_at', columns: ['published_at'])]
class OutboxMessage
{
    private const int DEFAULT_MAX_ATTEMPTS = 10;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    /**
     * @var array{class: string, data: array<string, mixed>}
     */
    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'published_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(name: 'max_attempts', type: 'integer', options: ['default' => 10])]
    private int $maxAttempts;

    #[ORM\Column(name: 'failed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $failedAt = null;

    /**
     * @param array{class: string, data: array<string, mixed>} $payload
     */
    public function __construct(
        Uuid $id,
        array $payload,
        \DateTimeImmutable $createdAt,
        int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
    ) {
        $this->id = $id;
        $this->payload = $payload;
        $this->createdAt = $createdAt;
        $this->maxAttempts = $maxAttempts;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return array{class: string, data: array<string, mixed>}
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function markPublished(\DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function incrementAttempts(): void
    {
        ++$this->attempts;
    }

    public function markFailed(\DateTimeImmutable $failedAt): void
    {
        $this->failedAt = $failedAt;
    }

    public function hasReachedMaxAttempts(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }
}
