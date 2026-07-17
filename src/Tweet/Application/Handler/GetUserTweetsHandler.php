<?php

declare(strict_types=1);

namespace App\Tweet\Application\Handler;

use App\Shared\Domain\Exception\InvalidUuidException;
use App\Tweet\Application\DTO\TweetDTO;
use App\Tweet\Application\DTO\TweetPageDTO;
use App\Tweet\Application\Query\GetUserTweetsQuery;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Reads user tweets via DBAL (no ORM hydration), cursor-paginated by createdAt+id.
 */
#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetUserTweetsHandler
{
    private const int MAX_LIMIT = 50;

    public function __construct(
        private Connection $connection,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     * @throws UserNotFoundException
     * @throws InvalidUuidException
     * @throws Exception
     * @throws \JsonException
     */
    public function __invoke(GetUserTweetsQuery $query): TweetPageDTO
    {
        $userId = UserId::fromString($query->userId);
        if (null === $this->userRepository->findById($userId)) {
            throw new UserNotFoundException($userId);
        }

        $limit = max(1, min(self::MAX_LIMIT, $query->limit));

        $sql = <<<'SQL'
            SELECT id, author_id, text, likes_count, created_at
            FROM tweets
            WHERE author_id = :author_id
            SQL;

        $params = ['author_id' => $userId->toString()];
        $types = ['author_id' => ParameterType::STRING];

        if (null !== $query->cursor && '' !== $query->cursor) {
            [$cursorCreatedAt, $cursorId] = $this->decodeCursor($query->cursor);
            $sql .= ' AND (created_at, id) < (:cursor_created_at, :cursor_id)';
            $params['cursor_created_at'] = $cursorCreatedAt;
            $params['cursor_id'] = $cursorId;
            $types['cursor_created_at'] = Types::DATETIME_IMMUTABLE;
            $types['cursor_id'] = ParameterType::STRING;
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit';
        $params['limit'] = $limit;
        $types['limit'] = ParameterType::INTEGER;

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);

        $items = [];
        foreach ($rows as $row) {
            $createdAt = $this->normalizeDateTime($row['created_at']);
            $items[] = new TweetDTO(
                id: (string) $row['id'],
                authorId: (string) $row['author_id'],
                text: (string) $row['text'],
                likesCount: (int) $row['likes_count'],
                createdAt: $createdAt->format(\DateTimeInterface::ATOM),
            );
        }

        $nextCursor = null;
        if (\count($items) === $limit) {
            $last = $items[array_key_last($items)];
            $nextCursor = $this->encodeCursor(
                new \DateTimeImmutable($last->createdAt),
                $last->id,
            );
        }

        return new TweetPageDTO($items, $nextCursor);
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: string}
     *
     * @throws \DateMalformedStringException
     */
    private function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        if (false === $decoded) {
            throw new \InvalidArgumentException('Invalid tweets cursor.');
        }

        $payload = json_decode($decoded, true);
        if (!\is_array($payload) || !isset($payload['createdAt'], $payload['id'])) {
            throw new \InvalidArgumentException('Invalid tweets cursor payload.');
        }

        return [
            new \DateTimeImmutable((string) $payload['createdAt']),
            (string) $payload['id'],
        ];
    }

    private function encodeCursor(\DateTimeImmutable $createdAt, string $id): string
    {
        return base64_encode(json_encode([
            'createdAt' => $createdAt->format(\DateTimeInterface::ATOM),
            'id' => $id,
        ], \JSON_THROW_ON_ERROR));
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function normalizeDateTime(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        return new \DateTimeImmutable((string) $value);
    }
}
