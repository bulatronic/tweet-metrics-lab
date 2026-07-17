<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Shared\Domain\EventPublisherInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Persists domain events into outbox_messages within the current Doctrine transaction.
 * Does not talk to RabbitMQ — that is the relay command's job.
 */
final readonly class DoctrineOutboxPublisher implements EventPublisherInterface
{
    public function __construct(
        private OutboxMessageRepository $outboxMessageRepository,
        private SerializerInterface $serializer,
    ) {
    }

    public function publish(object $domainEvent): void
    {
        $data = $this->serializer->normalize($domainEvent, 'json', [
            DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        if (!\is_array($data)) {
            throw new \RuntimeException(sprintf('Failed to normalize domain event "%s" for outbox.', $domainEvent::class));
        }

        /** @var array<string, mixed> $data */
        $message = new OutboxMessage(
            Uuid::v7(),
            [
                'class' => $domainEvent::class,
                'data' => $data,
            ],
            new \DateTimeImmutable(),
        );

        $this->outboxMessageRepository->save($message);
    }
}
