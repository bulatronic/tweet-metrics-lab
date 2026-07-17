<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class OutboxMessageRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(OutboxMessage $message): void
    {
        $this->entityManager->persist($message);
        // Flush is intentionally left to the surrounding Doctrine transaction.
    }

    /**
     * Locks a batch of unpublished, non-dead rows (SKIP LOCKED) for concurrent relay workers.
     *
     * @return list<OutboxMessage>
     *
     * @throws Exception
     */
    public function findUnpublishedForUpdate(int $limit): array
    {
        $connection = $this->entityManager->getConnection();

        $ids = $connection->fetchFirstColumn(
            'SELECT id FROM outbox_messages WHERE published_at IS NULL AND failed_at IS NULL ORDER BY created_at ASC LIMIT ? FOR UPDATE SKIP LOCKED',
            [$limit],
            [\Doctrine\DBAL\ParameterType::INTEGER],
        );

        if ([] === $ids) {
            return [];
        }

        $uuids = array_map(
            static fn (string $id): Uuid => Uuid::fromString($id),
            $ids,
        );

        /** @var list<OutboxMessage> $messages */
        $messages = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(OutboxMessage::class, 'o')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $uuids)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $messages;
    }

    public function countPending(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(OutboxMessage::class, 'o')
            ->where('o.publishedAt IS NULL')
            ->andWhere('o.failedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
