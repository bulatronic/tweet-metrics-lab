<?php

declare(strict_types=1);

namespace App\Tweet\Application\Handler;

use App\Tweet\Application\DTO\FeedItemDTO;
use App\Tweet\Application\DTO\FeedPageDTO;
use App\Tweet\Application\Query\GetFeedQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Reads the feed projection via DBAL (no ORM hydration), with short-lived Redis cache.
 */
#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetFeedQueryHandler
{
    private const int MAX_LIMIT = 50;
    private const int CACHE_TTL_SECONDS = 30;

    public function __construct(
        private Connection $connection,
        #[Autowire(service: 'cache.feed')]
        private CacheInterface $feedCache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(GetFeedQuery $query): FeedPageDTO
    {
        $limit = max(1, min(self::MAX_LIMIT, $query->limit));
        $cursor = $query->cursor;
        $cacheKey = sprintf('feed_%s_%d', $cursor ?? 'start', $limit);

        /** @var FeedPageDTO $page */
        $page = $this->feedCache->get($cacheKey, function (ItemInterface $item) use ($limit, $cursor): FeedPageDTO {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            return $this->fetchPage($limit, $cursor);
        });

        return $page;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     * @throws \JsonException
     */
    private function fetchPage(int $limit, ?string $cursor): FeedPageDTO
    {
        $sql = <<<'SQL'
            SELECT id, tweet_id, author_id, author_username, text, likes_count, created_at
            FROM feed_items
            SQL;

        $params = [];
        $types = [];

        if (null !== $cursor && '' !== $cursor) {
            [$cursorCreatedAt, $cursorId] = $this->decodeCursor($cursor);
            $sql .= ' WHERE (created_at, id) < (:cursor_created_at, :cursor_id)';
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
            $items[] = new FeedItemDTO(
                id: (string) $row['id'],
                tweetId: (string) $row['tweet_id'],
                authorId: (string) $row['author_id'],
                authorUsername: (string) $row['author_username'],
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

        return new FeedPageDTO($items, $nextCursor);
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function decodeCursor(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);
        if (false === $decoded) {
            throw new \InvalidArgumentException('Invalid feed cursor.');
        }

        $payload = json_decode($decoded, true);
        if (!\is_array($payload) || !isset($payload['createdAt'], $payload['id'])) {
            throw new \InvalidArgumentException('Invalid feed cursor payload.');
        }

        return [
            new \DateTimeImmutable((string) $payload['createdAt']),
            (string) $payload['id'],
        ];
    }

    /**
     * @throws \JsonException
     */
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
